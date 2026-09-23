<?php

namespace App\Services;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\CustomerOnHoldException;
use App\Models\Customer;
use App\Models\JobCard;
use App\Models\SalesDocument;
use App\Models\VehicleServiceHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Workshop invoicing + job costing (Modules 4.8/4.6). Converts a completed job
 * into a tax invoice through the Sales/AR system. Warranty lines are excluded;
 * parts COGS was already booked at issue, so this recognises REVENUE only —
 * parts revenue to 4100, labour revenue to 4300.
 */
class WorkshopInvoiceService
{
    public function __construct(
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
        private readonly PricingService $pricing,
    ) {
    }

    /**
     * @param array $payments [['method'=>cash|card|eft|account, 'amount'=>, 'tendered'=>?, 'reference'=>?], …]
     *                        empty → fully on account (customer's terms).
     */
    public function invoiceJob(JobCard $job, array $payments = [], ?int $userId = null): SalesDocument
    {
        if ($job->status !== 'completed') {
            throw new InvalidArgumentException("Job {$job->job_number} must be completed before invoicing.");
        }
        if (! $job->customer_id) {
            throw new InvalidArgumentException('The job has no customer to invoice.');
        }

        $job->load('labours', 'parts', 'customer', 'vehicle');
        $customer = $job->customer;

        // Billable lines (exclude warranty and returned parts).
        $partLines = $job->parts
            ->where('is_warranty', false)
            ->where('status', 'issued')
            ->values();
        $labourLines = $job->labours->where('is_warranty', false)->values();

        if ($partLines->isEmpty() && $labourLines->isEmpty()) {
            throw new InvalidArgumentException('Nothing billable on this job (all lines are warranty or unissued).');
        }

        $partsExcl = 0.0;
        $labourExcl = 0.0;
        $vatTotal = 0.0;
        $built = [];

        foreach ($partLines as $p) {
            $m = $this->pricing->computeLine((float) $p->qty, (float) $p->unit_price, 0);
            $partsExcl += $m['line_total_excl'];
            $vatTotal += $m['vat_amount'];
            $built[] = ['type' => 'part', 'model' => $p, 'maths' => $m];
        }
        foreach ($labourLines as $l) {
            $m = $this->pricing->computeLine((float) $l->hours, (float) $l->rate, 0);
            $labourExcl += $m['line_total_excl'];
            $vatTotal += $m['vat_amount'];
            $built[] = ['type' => 'labour', 'model' => $l, 'maths' => $m];
        }

        $subtotal = round($partsExcl + $labourExcl, 2);
        $total = round($subtotal + $vatTotal, 2);

        // Payments (default: fully on account).
        if ($payments === []) {
            $payments = [['method' => 'account', 'amount' => $total]];
        }
        $paid = round(array_sum(array_map(fn ($p) => (float) $p['amount'], $payments)), 2);
        if (abs($paid - $total) > 0.009) {
            throw new InvalidArgumentException(sprintf('Payments (%.2f) must equal the invoice total (%.2f).', $paid, $total));
        }

        $accountAmount = round(array_sum(array_map(fn ($p) => $p['method'] === 'account' ? (float) $p['amount'] : 0, $payments)), 2);
        if ($accountAmount > 0) {
            if ($customer->is_walk_in || $customer->type === 'cash') {
                throw new InvalidArgumentException('A cash customer cannot be invoiced on account.');
            }
            if ($customer->on_hold) {
                throw new CustomerOnHoldException($customer->name, $customer->hold_reason);
            }
            $balance = $customer->arBalance();
            if ($balance + $accountAmount > (float) $customer->credit_limit + 0.009) {
                throw new CreditLimitExceededException($customer->name, (float) $customer->credit_limit, $balance, $accountAmount);
            }
        }

        return DB::transaction(function () use ($job, $customer, $built, $payments, $subtotal, $partsExcl, $labourExcl, $vatTotal, $total, $accountAmount, $userId) {
            $invoice = SalesDocument::create([
                'document_number' => $this->sequences->next('invoice', $job->branch_id),
                'document_type' => 'invoice',
                'status' => 'posted',
                'customer_id' => $customer->id,
                'branch_id' => $job->branch_id,
                'salesperson_id' => $userId,
                'document_date' => now()->toDateString(),
                'subtotal_excl' => $subtotal,
                'vat_amount' => $vatTotal,
                'total_incl' => $total,
                'notes' => "Workshop job {$job->job_number}".($job->vehicle ? ' — '.$job->vehicle->label() : ''),
                'posted_at' => now(),
            ]);

            foreach ($built as $b) {
                $m = $b['maths'];
                if ($b['type'] === 'part') {
                    $invoice->lines()->create([
                        'part_id' => $b['model']->part_id,
                        'line_type' => 'part',
                        'description' => $b['model']->description,
                        'qty' => $b['model']->qty,
                        'unit_price' => $b['model']->unit_price,
                        'vat_rate' => $m['vat_rate'], 'vat_amount' => $m['vat_amount'],
                        'line_total_excl' => $m['line_total_excl'], 'line_total_incl' => $m['line_total_incl'],
                        'unit_cost' => $b['model']->unit_cost,
                    ]);
                } else {
                    $invoice->lines()->create([
                        'part_id' => null,
                        'line_type' => 'labour',
                        'labour_code' => $b['model']->labourCode?->code,
                        'description' => $b['model']->description,
                        'qty' => $b['model']->hours,
                        'unit_price' => $b['model']->rate,
                        'vat_rate' => $m['vat_rate'], 'vat_amount' => $m['vat_amount'],
                        'line_total_excl' => $m['line_total_excl'], 'line_total_incl' => $m['line_total_incl'],
                    ]);
                }
            }

            foreach ($payments as $payment) {
                $invoice->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'tendered' => $payment['tendered'] ?? null,
                    'change_given' => isset($payment['tendered']) ? max(0, round((float) $payment['tendered'] - (float) $payment['amount'], 2)) : 0,
                    'reference' => $payment['reference'] ?? null,
                    'received_by' => $userId,
                ]);
            }

            // Revenue-only GL (COGS was booked at part issue).
            $glLines = [];
            $cash = round(array_sum(array_map(fn ($p) => $p['method'] === 'cash' ? (float) $p['amount'] : 0, $payments)), 2);
            $bank = round(array_sum(array_map(fn ($p) => in_array($p['method'], ['card', 'eft'], true) ? (float) $p['amount'] : 0, $payments)), 2);
            if ($cash > 0) {
                $glLines[] = ['account' => '1110', 'debit' => $cash, 'credit' => 0, 'description' => 'Cash'];
            }
            if ($bank > 0) {
                $glLines[] = ['account' => '1120', 'debit' => $bank, 'credit' => 0, 'description' => 'Card/EFT'];
            }
            if ($accountAmount > 0) {
                $glLines[] = ['account' => '1210', 'debit' => $accountAmount, 'credit' => 0, 'description' => 'On account'];
            }
            if ($partsExcl > 0.005) {
                $glLines[] = ['account' => '4100', 'debit' => 0, 'credit' => round($partsExcl, 2), 'description' => 'Parts revenue'];
            }
            if ($labourExcl > 0.005) {
                $glLines[] = ['account' => '4300', 'debit' => 0, 'credit' => round($labourExcl, 2), 'description' => 'Labour revenue'];
            }
            if ($vatTotal > 0.005) {
                $glLines[] = ['account' => '2210', 'debit' => 0, 'credit' => $vatTotal, 'description' => 'VAT output'];
            }

            $journal = $this->gl->post(
                journalType: 'sales',
                date: now(),
                description: "Workshop invoice {$invoice->document_number} — {$job->job_number}",
                lines: $glLines,
                reference: $invoice->document_number,
                sourceType: SalesDocument::class,
                sourceId: $invoice->id,
                branch: $job->branch_id,
                userId: $userId,
                fromSubLedger: true,
            );

            $invoice->update(['gl_journal_id' => $journal->id]);

            $job->update([
                'status' => 'invoiced',
                'sales_document_id' => $invoice->id,
                'invoiced_at' => now(),
            ]);

            // Service history (Module 4.5).
            if ($job->vehicle_id) {
                VehicleServiceHistory::create([
                    'vehicle_id' => $job->vehicle_id,
                    'job_card_id' => $job->id,
                    'service_date' => now()->toDateString(),
                    'odometer' => $job->odometer_out ?? $job->odometer_in,
                    'summary' => $job->work_done ?: ($job->reported_fault ?: 'Workshop service'),
                ]);
            }

            return $invoice->fresh(['lines', 'payments']);
        });
    }
}
