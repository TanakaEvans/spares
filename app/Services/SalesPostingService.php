<?php

namespace App\Services;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\CustomerOnHoldException;
use App\Models\Customer;
use App\Models\SalesDocument;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The revenue engine (Modules 2.1/2.4/2.5, order-to-cash workflow).
 * Invoices post atomically: gapless number, stock SALE at AVCO,
 * GL (till/bank/debtors ← revenue + VAT output; COGS ← inventory).
 * Credit notes reverse against the original invoice only.
 */
class SalesPostingService
{
    public function __construct(
        private readonly StockLedgerService $stock,
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * @param array $lines    [['part_id'=>, 'qty'=>, 'unit_price'=>excl, 'discount_pct'=>0], …]
     * @param array $payments [['method'=>cash|card|eft|account, 'amount'=>, 'tendered'=>?, 'reference'=>?], …]
     */
    public function postInvoice(
        Customer $customer,
        int $branchId,
        array $lines,
        array $payments,
        ?int $userId = null,
        ?SalesDocument $parentOrder = null,
    ): SalesDocument {
        if ($lines === []) {
            throw new InvalidArgumentException('An invoice needs at least one line.');
        }

        // Totals first (validation before any writes).
        $computed = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $vatTotal = 0.0;

        foreach ($lines as $line) {
            $maths = $this->pricing->computeLine(
                (float) $line['qty'],
                (float) $line['unit_price'],
                (float) ($line['discount_pct'] ?? 0),
            );
            $computed[] = [$line, $maths];
            $subtotal += $maths['line_total_excl'];
            $discountTotal += $maths['discount_amount'];
            $vatTotal += $maths['vat_amount'];
        }

        $total = round($subtotal + $vatTotal, 2);

        $paymentsTotal = round(array_sum(array_map(fn ($p) => (float) $p['amount'], $payments)), 2);
        if (abs($paymentsTotal - $total) > 0.009) {
            throw new InvalidArgumentException(
                sprintf('Payments (%.2f) must equal the invoice total (%.2f).', $paymentsTotal, $total)
            );
        }

        // Credit checks for any on-account portion (Module 5.3).
        $accountAmount = round(array_sum(array_map(
            fn ($p) => $p['method'] === 'account' ? (float) $p['amount'] : 0,
            $payments
        )), 2);

        if ($accountAmount > 0) {
            if ($customer->is_walk_in || $customer->type === 'cash') {
                throw new InvalidArgumentException('The walk-in Cash Customer cannot buy on account.');
            }
            if ($customer->on_hold) {
                throw new CustomerOnHoldException($customer->name, $customer->hold_reason);
            }
            $balance = $customer->arBalance();
            if ($balance + $accountAmount > (float) $customer->credit_limit + 0.009) {
                throw new CreditLimitExceededException(
                    $customer->name, (float) $customer->credit_limit, $balance, $accountAmount
                );
            }
        }

        return DB::transaction(function () use ($customer, $branchId, $computed, $payments, $userId, $parentOrder, $subtotal, $discountTotal, $vatTotal, $total, $accountAmount) {
            $invoice = SalesDocument::create([
                'document_number' => $this->sequences->next('invoice', $branchId),
                'document_type' => 'invoice',
                'status' => 'posted',
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'salesperson_id' => $userId,
                'document_date' => now()->toDateString(),
                'parent_id' => $parentOrder?->id,
                'subtotal_excl' => $subtotal,
                'discount_amount' => $discountTotal,
                'vat_amount' => $vatTotal,
                'total_incl' => $total,
                'posted_at' => now(),
            ]);

            $cogsTotal = 0.0;

            foreach ($computed as [$line, $maths]) {
                // If fulfilling an order, release its reservation first.
                if ($parentOrder !== null) {
                    $this->stock->releaseReservation((int) $line['part_id'], $branchId, (float) $line['qty']);
                }

                $entry = $this->stock->post(
                    (int) $line['part_id'],
                    $branchId,
                    'SALE',
                    (float) $line['qty'],
                    null,
                    SalesDocument::class,
                    $invoice->id,
                    null,
                    $userId,
                );

                $unitCost = (float) $entry->unit_cost;
                $cogsTotal += round((float) $line['qty'] * $unitCost, 2);

                $invoice->lines()->create([
                    'part_id' => $line['part_id'],
                    'description' => $line['description'] ?? \App\Models\Part::find($line['part_id'])->description,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'discount_pct' => $line['discount_pct'] ?? 0,
                    'vat_rate' => $maths['vat_rate'],
                    'vat_amount' => $maths['vat_amount'],
                    'line_total_excl' => $maths['line_total_excl'],
                    'line_total_incl' => $maths['line_total_incl'],
                    'unit_cost' => $unitCost,
                ]);
            }

            foreach ($payments as $payment) {
                $invoice->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'tendered' => $payment['tendered'] ?? null,
                    'change_given' => isset($payment['tendered'])
                        ? max(0, round((float) $payment['tendered'] - (float) $payment['amount'], 2))
                        : 0,
                    'reference' => $payment['reference'] ?? null,
                    'received_by' => $userId,
                ]);
            }

            // GL: money in by method; revenue + VAT out; COGS.
            $glLines = [];
            $cash = round(array_sum(array_map(fn ($p) => $p['method'] === 'cash' ? (float) $p['amount'] : 0, $payments)), 2);
            $bank = round(array_sum(array_map(fn ($p) => in_array($p['method'], ['card', 'eft'], true) ? (float) $p['amount'] : 0, $payments)), 2);

            if ($cash > 0) {
                $glLines[] = ['account' => '1110', 'debit' => $cash, 'credit' => 0, 'description' => 'Cash sale'];
            }
            if ($bank > 0) {
                $glLines[] = ['account' => '1120', 'debit' => $bank, 'credit' => 0, 'description' => 'Card/EFT sale'];
            }
            if ($accountAmount > 0) {
                $glLines[] = ['account' => '1210', 'debit' => $accountAmount, 'credit' => 0, 'description' => 'On account'];
            }
            $glLines[] = ['account' => '4100', 'debit' => 0, 'credit' => $subtotal, 'description' => 'Parts revenue'];
            if ($vatTotal > 0) {
                $glLines[] = ['account' => '2210', 'debit' => 0, 'credit' => $vatTotal, 'description' => 'VAT output'];
            }
            if ($cogsTotal > 0) {
                $glLines[] = ['account' => '5100', 'debit' => $cogsTotal, 'credit' => 0, 'description' => 'Cost of sales'];
                $glLines[] = ['account' => '1310', 'debit' => 0, 'credit' => $cogsTotal, 'description' => 'Stock sold'];
            }

            $journal = $this->gl->post(
                journalType: 'sales',
                date: now(),
                description: "Invoice {$invoice->document_number} — {$customer->name}",
                lines: $glLines,
                reference: $invoice->document_number,
                sourceType: SalesDocument::class,
                sourceId: $invoice->id,
                branch: $branchId,
                userId: $userId,
                fromSubLedger: true,
            );

            $invoice->update(['gl_journal_id' => $journal->id]);

            if ($parentOrder !== null) {
                $parentOrder->update(['status' => 'invoiced']);
            }

            return $invoice->fresh(['lines', 'payments']);
        });
    }

    /**
     * Credit note against an invoice (Module 2.5): qty caps, restock,
     * VAT reversed at the ORIGINAL line rate.
     *
     * @param array $lines [['line_id'=>invoice line, 'qty'=>, 'restock'=>true], …]
     */
    public function postCreditNote(
        SalesDocument $invoice,
        array $lines,
        string $mode, // refund_cash | account
        string $reason,
        ?int $userId = null,
    ): SalesDocument {
        if ($invoice->document_type !== 'invoice' || $invoice->status !== 'posted') {
            throw new InvalidArgumentException('Credit notes can only be raised against posted invoices.');
        }
        if (! in_array($mode, ['refund_cash', 'account'], true)) {
            throw new InvalidArgumentException('Invalid credit mode.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('A credit note needs at least one line.');
        }

        $invoiceLines = $invoice->lines()->get()->keyBy('id');

        foreach ($lines as $line) {
            $orig = $invoiceLines[$line['line_id']] ?? null;
            if ($orig === null) {
                throw new InvalidArgumentException('Credit line does not belong to this invoice.');
            }
            $remaining = (float) $orig->qty - (float) $orig->qty_credited;
            if ((float) $line['qty'] > $remaining + 0.001) {
                throw new InvalidArgumentException(
                    "Cannot credit {$line['qty']} of {$orig->description} — only {$remaining} remains creditable."
                );
            }
        }

        return DB::transaction(function () use ($invoice, $lines, $invoiceLines, $mode, $reason, $userId) {
            $subtotal = 0.0;
            $vatTotal = 0.0;
            $restockValue = 0.0;

            $credit = SalesDocument::create([
                'document_number' => $this->sequences->next('credit_note', $invoice->branch_id),
                'document_type' => 'credit_note',
                'status' => 'posted',
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'salesperson_id' => $userId,
                'document_date' => now()->toDateString(),
                'parent_id' => $invoice->id,
                'credit_mode' => $mode,
                'reason' => $reason,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $orig = $invoiceLines[$line['line_id']];
                $qty = (float) $line['qty'];
                $ratio = $qty / (float) $orig->qty;

                $excl = round((float) $orig->line_total_excl * $ratio, 2);
                $vat = round((float) $orig->vat_amount * $ratio, 2);

                $credit->lines()->create([
                    'part_id' => $orig->part_id,
                    'description' => $orig->description,
                    'qty' => $qty,
                    'unit_price' => $orig->unit_price,
                    'discount_pct' => $orig->discount_pct,
                    'vat_rate' => $orig->vat_rate, // ORIGINAL rate — returns-and-credits.md
                    'vat_amount' => $vat,
                    'line_total_excl' => $excl,
                    'line_total_incl' => round($excl + $vat, 2),
                    'unit_cost' => $orig->unit_cost,
                    'source_line_id' => $orig->id,
                ]);

                $subtotal += $excl;
                $vatTotal += $vat;

                if ($line['restock'] ?? true) {
                    $this->stock->post(
                        $orig->part_id, $invoice->branch_id, 'RETURN_IN',
                        $qty, (float) $orig->unit_cost,
                        SalesDocument::class, $credit->id, $reason, $userId,
                    );
                    $restockValue += round($qty * (float) $orig->unit_cost, 2);
                }

                $orig->update(['qty_credited' => (float) $orig->qty_credited + $qty]);
            }

            $total = round($subtotal + $vatTotal, 2);
            $credit->update([
                'subtotal_excl' => $subtotal,
                'vat_amount' => $vatTotal,
                'total_incl' => $total,
            ]);

            // GL reversal.
            $glLines = [
                ['account' => '4900', 'debit' => $subtotal, 'credit' => 0, 'description' => 'Sales return'],
            ];
            if ($vatTotal > 0) {
                $glLines[] = ['account' => '2210', 'debit' => $vatTotal, 'credit' => 0, 'description' => 'VAT output reversal'];
            }
            $glLines[] = [
                'account' => $mode === 'refund_cash' ? '1110' : '1210',
                'debit' => 0, 'credit' => $total,
                'description' => $mode === 'refund_cash' ? 'Cash refund' : 'Credit to account',
            ];
            if ($restockValue > 0) {
                $glLines[] = ['account' => '1310', 'debit' => $restockValue, 'credit' => 0, 'description' => 'Stock returned'];
                $glLines[] = ['account' => '5100', 'debit' => 0, 'credit' => $restockValue, 'description' => 'COGS reversal'];
            }

            $journal = $this->gl->post(
                journalType: 'sales',
                date: now(),
                description: "Credit note {$credit->document_number} against {$invoice->document_number}",
                lines: $glLines,
                reference: $credit->document_number,
                sourceType: SalesDocument::class,
                sourceId: $credit->id,
                branch: $invoice->branch_id,
                userId: $userId,
                fromSubLedger: true,
            );

            $credit->update(['gl_journal_id' => $journal->id]);

            return $credit->fresh('lines');
        });
    }
}
