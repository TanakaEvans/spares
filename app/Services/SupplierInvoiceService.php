<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 3-way matching (Module 3.4): PO ↔ GRN ↔ supplier invoice.
 * Within tolerance → posted (DR 2120 Accruals + 2220 VAT Input / CR 2110
 * Creditors). Outside tolerance → disputed, excluded from payment runs.
 */
class SupplierInvoiceService
{
    public function __construct(
        private readonly GlPostingService $gl,
        private readonly SettingsService $settings,
    ) {
    }

    public function capture(
        GoodsReceivedNote $grn,
        string $supplierRef,
        string $invoiceDate,
        float $subtotal,
        float $vatAmount,
        ?int $userId = null,
    ): SupplierInvoice {
        if ($grn->status !== 'posted') {
            throw new InvalidArgumentException('Only posted GRNs can be invoiced.');
        }

        if (SupplierInvoice::where('grn_id', $grn->id)->whereIn('status', ['matched', 'posted'])->exists()) {
            throw new InvalidArgumentException("GRN {$grn->grn_number} already has a matched invoice.");
        }

        $total = round($subtotal + $vatAmount, 2);
        $grnValue = $grn->acceptedValue();

        // Tolerance: max(configured %, $10 absolute) per Module 3.4.
        $tolerancePct = (float) $this->settings->get('purchasing.invoice_match_tolerance_pct');
        $allowedVariance = max($grnValue * $tolerancePct / 100, 10.0);
        $variance = abs($subtotal - $grnValue);
        $withinTolerance = $variance <= $allowedVariance;

        return DB::transaction(function () use ($grn, $supplierRef, $invoiceDate, $subtotal, $vatAmount, $total, $withinTolerance, $variance, $grnValue, $userId) {
            $invoice = SupplierInvoice::create([
                'invoice_number' => 'SINV-'.now()->format('Ymd').'-'.str_pad((string) (SupplierInvoice::count() + 1), 4, '0', STR_PAD_LEFT),
                'supplier_ref' => $supplierRef,
                'supplier_id' => $grn->supplier_id,
                'grn_id' => $grn->id,
                'invoice_date' => $invoiceDate,
                'due_date' => now()->parse($invoiceDate)->addDays($grn->supplier->payment_terms_days ?? 30)->toDateString(),
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => $withinTolerance ? 'matched' : 'disputed',
                'dispute_reason' => $withinTolerance ? null : sprintf(
                    'Invoice subtotal %.2f differs from GRN value %.2f by %.2f — beyond tolerance.',
                    $subtotal, $grnValue, $variance
                ),
            ]);

            if ($withinTolerance) {
                $this->post($invoice, $userId);
            }

            return $invoice;
        });
    }

    /** Post a matched (or dispute-resolved) invoice to AP. */
    public function post(SupplierInvoice $invoice, ?int $userId = null): SupplierInvoice
    {
        if ($invoice->status === 'posted') {
            throw new InvalidArgumentException("Invoice {$invoice->invoice_number} is already posted.");
        }

        return DB::transaction(function () use ($invoice, $userId) {
            $lines = [
                ['account' => '2120', 'debit' => (float) $invoice->subtotal, 'credit' => 0, 'description' => 'Clear GRN accrual'],
            ];
            if ((float) $invoice->vat_amount > 0) {
                $lines[] = ['account' => '2220', 'debit' => (float) $invoice->vat_amount, 'credit' => 0, 'description' => 'VAT input'];
            }
            $lines[] = ['account' => '2110', 'debit' => 0, 'credit' => (float) $invoice->total, 'description' => 'Trade creditor'];

            $journal = $this->gl->post(
                journalType: 'purchase',
                date: $invoice->invoice_date,
                description: "Supplier invoice {$invoice->supplier_ref} — {$invoice->supplier->name}",
                lines: $lines,
                reference: $invoice->invoice_number,
                sourceType: SupplierInvoice::class,
                sourceId: $invoice->id,
                branch: $invoice->grn->branch_id,
                userId: $userId,
                fromSubLedger: true,
            );

            $invoice->update([
                'status' => 'posted',
                'gl_journal_id' => $journal->id,
                'posted_at' => now(),
            ]);

            return $invoice;
        });
    }
}
