<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\StockLevel;
use App\Services\PricingService;
use App\Services\SalesPostingService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Counter sales (Module 2.1). The keyboard-first till: scan → cart → tender.
 * All posting flows through SalesPostingService; this controller only shapes
 * HTTP and resolves authoritative prices server-side.
 */
class PointOfSaleController extends Controller
{
    public function create(Request $request, SettingsService $settings): Response
    {
        $branch = $this->activeBranch($request);

        return Inertia::render('Sales/Pos/Terminal', [
            'branch' => $branch->only(['id', 'name']),
            'walkIn' => Customer::walkIn()->only(['id', 'name', 'customer_number']),
            'maxDiscount' => (float) $settings->get('sales.max_discount_without_approval', $branch->id),
            'vatRate' => (float) $settings->get('tax.vat_rate_default'),
        ]);
    }

    /** Scan/search → price + availability at the active branch. */
    public function partLookup(Request $request, PricingService $pricing): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $branch = $this->activeBranch($request);
        $customer = $request->integer('customer_id') ? Customer::find($request->integer('customer_id')) : null;

        if (mb_strlen($q) < 2) {
            return response()->json(['parts' => []]);
        }

        $parts = Part::search($q)->active()->limit(8)->get()
            ->map(function (Part $p) use ($pricing, $customer, $branch) {
                try {
                    $price = $pricing->priceFor($p, $customer);
                    $priced = true;
                } catch (\App\Exceptions\UnpricedPartException) {
                    $price = 0.0;
                    $priced = false;
                }

                $onHand = (float) (StockLevel::where('part_id', $p->id)
                    ->where('branch_id', $branch->id)->value('qty_on_hand') ?? 0);

                return [
                    'id' => $p->id,
                    'part_number' => $p->part_number,
                    'description' => $p->description,
                    'price' => round($price, 2),
                    'priced' => $priced,
                    'on_hand' => $onHand,
                ];
            });

        return response()->json(['parts' => $parts]);
    }

    /** Customer search for account/named sales. */
    public function customerLookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = Customer::active()
            ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")
                ->orWhere('customer_number', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"))
            ->orderBy('name')->limit(8)->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'customer_number' => $c->customer_number,
                'is_walk_in' => $c->is_walk_in,
                'on_hold' => $c->on_hold,
                'credit_limit' => (float) $c->credit_limit,
                'ar_balance' => $c->arBalance(),
                'available_credit' => round((float) $c->credit_limit - $c->arBalance(), 2),
            ]);

        return response()->json(['customers' => $customers]);
    }

    public function store(Request $request, SalesPostingService $sales, PricingService $pricing, SettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,card,eft,account'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'discount_approved' => ['nullable', 'boolean'],
        ]);

        $branch = $this->activeBranch($request);
        $customer = Customer::findOrFail($data['customer_id']);
        $maxDiscount = (float) $settings->get('sales.max_discount_without_approval', $branch->id);

        // Resolve authoritative prices server-side; enforce the discount gate.
        $lines = [];
        foreach ($data['lines'] as $line) {
            $discount = (float) ($line['discount_pct'] ?? 0);
            if ($discount > $maxDiscount && ! ($data['discount_approved'] ?? false)) {
                return back()->withErrors([
                    'discount' => "A discount of {$discount}% exceeds the {$maxDiscount}% limit — a supervisor must approve it.",
                ]);
            }

            try {
                $unitPrice = $pricing->priceFor((int) $line['part_id'], $customer);
            } catch (DomainException $e) {
                return back()->withErrors(['lines' => $e->userMessage()]);
            }

            $lines[] = [
                'part_id' => (int) $line['part_id'],
                'qty' => (float) $line['qty'],
                'unit_price' => $unitPrice,
                'discount_pct' => $discount,
            ];
        }

        try {
            $invoice = $sales->postInvoice($customer, $branch->id, $lines, $data['payments'], $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['sale' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->document_number} posted — ".number_format((float) $invoice->total_incl, 2).'.');
    }

    private function activeBranch(Request $request): Branch
    {
        // The till trades on the main branch until per-user active-branch
        // switching lands (tracked in docs/tasks/05-customers.md).
        return Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();
    }
}
