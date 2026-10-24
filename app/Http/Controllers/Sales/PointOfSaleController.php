<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Services\PosTerminalService;
use App\Services\PricingService;
use App\Services\SalesPostingService;
use App\Services\SettingsService;
use App\Services\SupervisorApprovalService;
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
    public function create(Request $request, SettingsService $settings, PosTerminalService $till): Response
    {
        $branch = $this->activeBranch($request);

        return Inertia::render('Sales/Pos/Terminal', [
            'branch' => $branch->only(['id', 'name']),
            'walkIn' => Customer::walkIn()->only(['id', 'name', 'customer_number']),
            'maxDiscount' => (float) $settings->get('sales.max_discount_without_approval', $branch->id),
            'vatRate' => (float) $settings->get('tax.vat_rate_default'),
            'allowNegative' => (bool) $settings->get('inventory.allow_negative_stock', $branch->id),
            'topSellers' => $till->topSellers($branch),
            'categories' => $till->categories(),
            'today' => $till->todayFor($request->user()->id, $branch),
            'completedSale' => $request->session()->get('pos_sale'),
        ]);
    }

    /**
     * Scan/search/browse → price, availability, bin and supersession at the
     * active branch. exact=1: a scanned barcode / typed part number followed by
     * Enter — one hit on part number or either barcode, no fuzzy matching.
     */
    public function partLookup(Request $request, PosTerminalService $till): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('category_id') ?: null;
        if (mb_strlen($q) < 2 && ! $categoryId) {
            return response()->json(['parts' => []]);
        }
        $customer = $request->integer('customer_id') ? Customer::find($request->integer('customer_id')) : null;

        return response()->json([
            'parts' => $till->lookup($this->activeBranch($request), $customer, $q, $request->boolean('exact'), $categoryId, $categoryId ? 40 : 20),
        ]);
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

    public function store(Request $request, SalesPostingService $sales, PricingService $pricing, SettingsService $settings, PosTerminalService $till): RedirectResponse
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
            'supervisor_username' => ['nullable', 'string', 'max:150'],
            'supervisor_password' => ['nullable', 'string', 'max:200'],
        ]);

        $branch = $this->activeBranch($request);
        $customer = Customer::findOrFail($data['customer_id']);
        $maxDiscount = (float) $settings->get('sales.max_discount_without_approval', $branch->id);

        // Over-limit discounts need a supervisor's own credentials (2.1 rule 3) —
        // verified server-side, recorded on the invoice and in the audit log.
        $overLimit = array_values(array_filter($data['lines'], fn ($l) => (float) ($l['discount_pct'] ?? 0) > $maxDiscount));
        $approver = null;
        if ($overLimit !== []) {
            try {
                $approver = app(SupervisorApprovalService::class)->approve(
                    $data['supervisor_username'] ?? null,
                    $data['supervisor_password'] ?? null,
                    'sales.pos.approve-discount',
                    'sales.discount_override',
                    ['max_discount_pct' => $maxDiscount, 'customer_id' => $customer->id,
                        'lines' => array_map(fn ($l) => ['part_id' => $l['part_id'], 'qty' => $l['qty'], 'discount_pct' => $l['discount_pct']], $overLimit)],
                    $request->user()->id,
                );
            } catch (DomainException $e) {
                return back()->withErrors(['discount' => $e->userMessage()]);
            }
        }

        // Resolve authoritative prices server-side.
        $lines = [];
        foreach ($data['lines'] as $line) {
            $discount = (float) ($line['discount_pct'] ?? 0);

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
            $invoice = $sales->postInvoice($customer, $branch->id, $lines, $data['payments'], $request->user()->id, null,
                $approver ? ['discount_approved_by' => $approver->id] : []);
        } catch (DomainException $e) {
            return back()->withErrors(['sale' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        // Straight back to the till, ready for the next customer — the
        // completion screen offers the receipt, the A4 invoice and the change due.
        return redirect()->route('sales.pos')->with('pos_sale', $till->completedSummary($invoice));
    }

    /**
     * Pre-check a supervisor's credentials from the tender dialog so the cashier
     * gets an immediate answer; the sale POST verifies again before posting.
     */
    public function approveDiscount(Request $request, SupervisorApprovalService $approvals): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        try {
            $approver = $approvals->verify($data['username'], $data['password'], 'sales.pos.approve-discount', $request->user()->id);
        } catch (DomainException $e) {
            return response()->json(['ok' => false, 'message' => $e->userMessage()], 422);
        }

        return response()->json(['ok' => true, 'approver' => $approver->name]);
    }

    private function activeBranch(Request $request): Branch
    {
        // The till trades on the main branch until per-user active-branch
        // switching lands (tracked in docs/tasks/05-customers.md).
        return Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();
    }
}
