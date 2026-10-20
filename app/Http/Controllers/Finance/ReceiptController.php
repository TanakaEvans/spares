<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Services\ArReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function index(Request $request, ArReceiptService $ar): Response
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Finance/Receipts/Index', [
            'receipts' => CustomerReceipt::with('customer:id,name')
                ->latest('receipt_date')->latest('id')
                ->paginate(15)
                ->through(fn (CustomerReceipt $r) => [
                    'id' => $r->id,
                    'receipt_number' => $r->receipt_number,
                    'customer' => $r->customer?->name,
                    'customer_id' => $r->customer_id,
                    'receipt_date' => $r->receipt_date->toDateString(),
                    'method' => $r->method,
                    'amount' => (float) $r->amount,
                    'unallocated' => $r->unallocated(),
                ]),
            'ageing' => $ar->ageing($asOf),
            'filters' => ['as_of' => $asOf],
        ]);
    }

    public function show(CustomerReceipt $receipt): Response
    {
        $receipt->load([
            'customer:id,name,customer_number',
            'branch:id,name',
            'receivedBy:id,name',
            'journal:id,journal_number',
            'allocations.invoice:id,document_number,document_date,total_incl,status',
        ]);

        return Inertia::render('Finance/Receipts/Show', [
            'receipt' => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'receipt_date' => $receipt->receipt_date->toDateString(),
                'method' => $receipt->method,
                'amount' => (float) $receipt->amount,
                'allocated' => (float) $receipt->allocated,
                'unallocated' => $receipt->unallocated(),
                'reference' => $receipt->reference,
                'notes' => $receipt->notes,
                'posted_at' => $receipt->posted_at?->toDateTimeString(),
                'customer' => $receipt->customer ? [
                    'id' => $receipt->customer->id,
                    'name' => $receipt->customer->name,
                    'customer_number' => $receipt->customer->customer_number,
                ] : null,
                'branch' => $receipt->branch ? ['id' => $receipt->branch->id, 'name' => $receipt->branch->name] : null,
                'received_by' => $receipt->receivedBy ? ['id' => $receipt->receivedBy->id, 'name' => $receipt->receivedBy->name] : null,
                'journal' => $receipt->journal ? ['id' => $receipt->journal->id, 'journal_number' => $receipt->journal->journal_number] : null,
                'allocations' => $receipt->allocations->map(fn ($a) => [
                    'id' => $a->id,
                    'document_id' => $a->document_id,
                    'document_number' => $a->invoice?->document_number,
                    'document_date' => $a->invoice?->document_date?->toDateString(),
                    'document_total' => (float) ($a->invoice?->total_incl ?? 0),
                    'amount' => (float) $a->amount,
                ])->values(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Receipts/Create', [
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    /** Customer search + their open invoices (for the allocation grid). */
    public function customerLookup(Request $request, ArReceiptService $ar): JsonResponse
    {
        if ($request->filled('customer_id')) {
            $customer = Customer::findOrFail($request->integer('customer_id'));

            return response()->json([
                'customer' => $customer->only(['id', 'name', 'customer_number']),
                'ar_balance' => $customer->arBalance(),
                'open_invoices' => $ar->openInvoices($customer),
            ]);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['customers' => []]);
        }

        return response()->json([
            'customers' => Customer::where('is_walk_in', false)->active()
                ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('customer_number', 'like', "%{$q}%"))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'customer_number'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'customer_number' => $c->customer_number]),
        ]);
    }

    public function store(Request $request, ArReceiptService $ar): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'receipt_date' => ['required', 'date'],
            'method' => ['required', 'in:cash,card,eft,bank_transfer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.document_id' => ['required', 'exists:sales_documents,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $receipt = $ar->postReceipt(
                Customer::findOrFail($data['customer_id']),
                $data['branch_id'],
                $data['method'],
                (float) $data['amount'],
                $data['allocations'] ?? [],
                $data['reference'] ?? null,
                $data['receipt_date'],
                $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['receipt' => $e->getMessage()]);
        }

        return redirect()->route('finance.receipts.index')
            ->with('success', "Receipt {$receipt->receipt_number} posted.");
    }
}
