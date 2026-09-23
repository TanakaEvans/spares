<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesDocument;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Quotations (2.2) and sales orders (2.3). Neither touches stock value or the
 * GL — a quote is a priced offer, an order reserves availability. Money only
 * moves when the order is invoiced through SalesPostingService.
 */
class SalesQuoteService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
        private readonly StockLedgerService $stock,
    ) {
    }

    /**
     * @param array $lines [['part_id'=>, 'qty'=>, 'unit_price'=>excl?, 'discount_pct'=>0], …]
     *                     unit_price omitted → resolved from the customer's price list.
     */
    public function createQuote(Customer $customer, int $branchId, array $lines, ?int $userId = null): SalesDocument
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A quote needs at least one line.');
        }

        $expiryDays = (int) $this->settings->get('sales.quote_expiry_days');

        return DB::transaction(function () use ($customer, $branchId, $lines, $userId, $expiryDays) {
            $quote = SalesDocument::create([
                'document_number' => $this->sequences->next('quotation', $branchId),
                'document_type' => 'quotation',
                'status' => 'open',
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'salesperson_id' => $userId,
                'document_date' => now()->toDateString(),
                'expiry_date' => now()->addDays($expiryDays)->toDateString(),
            ]);

            $this->writeLines($quote, $customer, $lines);

            return $quote->fresh('lines');
        });
    }

    /**
     * Which quote lines would reprice if converted today (Module 2.3 rule).
     * Returns [['line_id'=>, 'part_id'=>, 'was'=>, 'now'=>], …]; empty = unchanged.
     */
    public function repriceCheck(SalesDocument $quote): array
    {
        $customer = $quote->customer;
        $changed = [];

        foreach ($quote->lines as $line) {
            $current = $this->pricing->priceFor($line->part_id, $customer);
            if (abs($current - (float) $line->unit_price) > 0.005) {
                $changed[] = [
                    'line_id' => $line->id,
                    'part_id' => $line->part_id,
                    'was' => (float) $line->unit_price,
                    'now' => $current,
                ];
            }
        }

        return $changed;
    }

    /**
     * Convert a quote to a confirmed order and reserve stock.
     * Honours the quoted prices unless $reprice is true (then re-resolves).
     */
    public function convertToOrder(SalesDocument $quote, bool $reprice = false, ?int $userId = null): SalesDocument
    {
        if ($quote->document_type !== 'quotation') {
            throw new InvalidArgumentException('Only quotations can be converted to orders.');
        }
        if ($quote->status !== 'open') {
            throw new InvalidArgumentException("Quote {$quote->document_number} is {$quote->status}.");
        }
        if ($quote->isExpired()) {
            throw new InvalidArgumentException("Quote {$quote->document_number} expired on {$quote->expiry_date->toDateString()} — re-quote at current prices.");
        }

        return DB::transaction(function () use ($quote, $reprice, $userId) {
            $customer = $quote->customer;

            $lines = $quote->lines->map(fn ($line) => [
                'part_id' => $line->part_id,
                'qty' => (float) $line->qty,
                'unit_price' => $reprice ? null : (float) $line->unit_price,
                'discount_pct' => (float) $line->discount_pct,
            ])->all();

            $order = SalesDocument::create([
                'document_number' => $this->sequences->next('sales_order', $quote->branch_id),
                'document_type' => 'order',
                'status' => 'confirmed',
                'customer_id' => $customer->id,
                'branch_id' => $quote->branch_id,
                'salesperson_id' => $userId,
                'document_date' => now()->toDateString(),
                'parent_id' => $quote->id,
            ]);

            $this->writeLines($order, $customer, $lines);

            // Reserve availability at the order branch.
            foreach ($order->lines as $line) {
                $this->stock->reserve($line->part_id, $quote->branch_id, (float) $line->qty);
            }

            $quote->update(['status' => 'converted']);

            return $order->fresh('lines');
        });
    }

    /** Cancel an unfulfilled order and release its reservations. */
    public function cancelOrder(SalesDocument $order, ?int $userId = null): SalesDocument
    {
        if ($order->document_type !== 'order') {
            throw new InvalidArgumentException('Not a sales order.');
        }
        if ($order->status !== 'confirmed') {
            throw new InvalidArgumentException("Order {$order->document_number} is {$order->status} and cannot be cancelled.");
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->lines as $line) {
                $this->stock->releaseReservation($line->part_id, $order->branch_id, (float) $line->qty);
            }
            $order->update(['status' => 'cancelled']);

            return $order;
        });
    }

    /** Resolve prices, compute line maths, persist lines and roll up totals. */
    private function writeLines(SalesDocument $doc, Customer $customer, array $lines): void
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $vat = 0.0;

        foreach ($lines as $line) {
            $unitPrice = $line['unit_price'] ?? $this->pricing->priceFor($line['part_id'], $customer);
            $maths = $this->pricing->computeLine(
                (float) $line['qty'],
                (float) $unitPrice,
                (float) ($line['discount_pct'] ?? 0),
            );

            $doc->lines()->create([
                'part_id' => $line['part_id'],
                'description' => \App\Models\Part::find($line['part_id'])->description,
                'qty' => $line['qty'],
                'unit_price' => $unitPrice,
                'discount_pct' => $line['discount_pct'] ?? 0,
                'vat_rate' => $maths['vat_rate'],
                'vat_amount' => $maths['vat_amount'],
                'line_total_excl' => $maths['line_total_excl'],
                'line_total_incl' => $maths['line_total_incl'],
            ]);

            $subtotal += $maths['line_total_excl'];
            $discount += $maths['discount_amount'];
            $vat += $maths['vat_amount'];
        }

        $doc->update([
            'subtotal_excl' => $subtotal,
            'discount_amount' => $discount,
            'vat_amount' => $vat,
            'total_incl' => round($subtotal + $vat, 2),
        ]);
    }
}
