<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Services\ApPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request, ApPaymentService $ap): Response
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Finance/Payments/Index', [
            'payments' => SupplierPayment::with('supplier:id,name')
                ->latest('payment_date')->latest('id')
                ->paginate(15)
                ->through(fn (SupplierPayment $p) => [
                    'id' => $p->id,
                    'payment_number' => $p->payment_number,
                    'supplier' => $p->supplier?->name,
                    'supplier_id' => $p->supplier_id,
                    'payment_date' => $p->payment_date->toDateString(),
                    'method' => $p->method,
                    'amount' => (float) $p->amount,
                    'batch_ref' => $p->batch_ref,
                ]),
            'ageing' => $ap->ageing($asOf),
            'filters' => ['as_of' => $asOf],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Payments/Create', [
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function supplierLookup(Request $request, ApPaymentService $ap): JsonResponse
    {
        if ($request->filled('supplier_id')) {
            $supplier = Supplier::findOrFail($request->integer('supplier_id'));

            return response()->json([
                'supplier' => $supplier->only(['id', 'name', 'supplier_number']),
                'ap_balance' => $supplier->apBalance(),
                'open_invoices' => $ap->openInvoices($supplier),
            ]);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['suppliers' => []]);
        }

        return response()->json([
            'suppliers' => Supplier::active()
                ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('supplier_number', 'like', "%{$q}%"))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'supplier_number'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'supplier_number' => $s->supplier_number]),
        ]);
    }

    public function store(Request $request, ApPaymentService $ap): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'in:eft,cash,cheque,bank_transfer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.supplier_invoice_id' => ['required', 'exists:supplier_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $payment = $ap->postPayment(
                Supplier::findOrFail($data['supplier_id']),
                $data['branch_id'],
                $data['method'],
                (float) $data['amount'],
                $data['allocations'] ?? [],
                $data['reference'] ?? null,
                $data['payment_date'],
                $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('finance.payments.index')
            ->with('success', "Payment {$payment->payment_number} posted.");
    }

    // ── Payment run ──────────────────────────────────────────────────────

    public function runIndex(Request $request, ApPaymentService $ap): Response
    {
        $due = SupplierInvoice::where('status', 'posted')
            ->with('supplier:id,name')
            ->orderBy('due_date')
            ->get()
            ->map(fn (SupplierInvoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'supplier' => $inv->supplier?->name,
                'supplier_id' => $inv->supplier_id,
                'invoice_date' => $inv->invoice_date->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'outstanding' => $ap->invoiceOutstanding($inv->id),
            ])
            ->filter(fn ($r) => $r['outstanding'] > 0.005)
            ->values();

        return Inertia::render('Finance/PaymentRun/Index', [
            'dueInvoices' => $due,
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function runExecute(Request $request, ApPaymentService $ap): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'method' => ['required', 'in:eft,cash,cheque,bank_transfer'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'exists:supplier_invoices,id'],
        ]);

        try {
            $result = $ap->runBatch($data['invoice_ids'], $data['branch_id'], $data['method'], $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return redirect()->route('finance.payments.index')
            ->with('success', count($result['payments'])." payment(s) posted under batch {$result['batch_ref']}.");
    }
}
