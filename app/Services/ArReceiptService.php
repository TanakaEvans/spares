<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\SalesDocument;
use App\Models\SalesPayment;
use App\Models\ReceiptAllocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Accounts Receivable (Module 7.3): capture customer receipts, allocate them
 * across open invoices, and age the debtors book. Posting runs through
 * GlPostingService — DR bank/till, CR Trade Debtors control (1210).
 */
class ArReceiptService
{
    public function __construct(
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    /** The on-account charge for an invoice (its 'account' payment lines). */
    public function invoiceAccountAmount(int $invoiceId): float
    {
        return round((float) SalesPayment::where('document_id', $invoiceId)
            ->where('method', 'account')->sum('amount'), 2);
    }

    /** Open (unsettled) account invoices for a customer, oldest first. */
    public function openInvoices(Customer $customer): array
    {
        $invoices = SalesDocument::where('customer_id', $customer->id)
            ->where('document_type', 'invoice')
            ->where('status', 'posted')
            ->orderBy('document_date')->orderBy('id')
            ->get(['id', 'document_number', 'document_date']);

        $rows = [];
        foreach ($invoices as $inv) {
            $charge = $this->invoiceAccountAmount($inv->id);
            if ($charge <= 0.005) {
                continue;
            }
            $allocated = round((float) ReceiptAllocation::where('document_id', $inv->id)->sum('amount'), 2);
            $outstanding = round($charge - $allocated, 2);
            if ($outstanding <= 0.005) {
                continue;
            }
            $rows[] = [
                'id' => $inv->id,
                'document_number' => $inv->document_number,
                'document_date' => $inv->document_date->toDateString(),
                'due_date' => $inv->document_date->copy()->addDays($customer->payment_terms_days)->toDateString(),
                'charge' => $charge,
                'allocated' => $allocated,
                'outstanding' => $outstanding,
            ];
        }

        return $rows;
    }

    /**
     * @param array $allocations [['document_id'=>, 'amount'=>], …] (optional)
     */
    public function postReceipt(
        Customer $customer,
        int $branchId,
        string $method,
        float $amount,
        array $allocations = [],
        ?string $reference = null,
        ?string $date = null,
        ?int $userId = null,
    ): CustomerReceipt {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Receipt amount must be positive.');
        }
        if (! in_array($method, ['cash', 'card', 'eft', 'bank_transfer'], true)) {
            throw new InvalidArgumentException('Invalid receipt method.');
        }

        $allocTotal = round(array_sum(array_map(fn ($a) => (float) $a['amount'], $allocations)), 2);
        if ($allocTotal > $amount + 0.009) {
            throw new InvalidArgumentException('Allocations exceed the receipt amount.');
        }

        // Validate each allocation against the invoice's outstanding.
        foreach ($allocations as $a) {
            $charge = $this->invoiceAccountAmount((int) $a['document_id']);
            $already = round((float) ReceiptAllocation::where('document_id', $a['document_id'])->sum('amount'), 2);
            $outstanding = round($charge - $already, 2);
            if ((float) $a['amount'] > $outstanding + 0.009) {
                throw new InvalidArgumentException("Allocation of {$a['amount']} exceeds the invoice's outstanding {$outstanding}.");
            }
        }

        return DB::transaction(function () use ($customer, $branchId, $method, $amount, $allocations, $allocTotal, $reference, $date, $userId) {
            $receipt = CustomerReceipt::create([
                'receipt_number' => $this->sequences->next('receipt', $branchId),
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'receipt_date' => $date ?? now()->toDateString(),
                'method' => $method,
                'amount' => $amount,
                'allocated' => $allocTotal,
                'reference' => $reference,
                'received_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($allocations as $a) {
                if ((float) $a['amount'] <= 0) {
                    continue;
                }
                $receipt->allocations()->create([
                    'document_id' => $a['document_id'],
                    'amount' => $a['amount'],
                ]);
            }

            $bankAccount = $method === 'cash' ? '1110' : '1120';
            $journal = $this->gl->post(
                journalType: 'receipt',
                date: $receipt->receipt_date,
                description: "Receipt {$receipt->receipt_number} — {$customer->name}",
                lines: [
                    ['account' => $bankAccount, 'debit' => $amount, 'credit' => 0, 'description' => ucfirst($method).' received'],
                    ['account' => '1210', 'debit' => 0, 'credit' => $amount, 'description' => 'Debtors settlement'],
                ],
                reference: $receipt->receipt_number,
                sourceType: CustomerReceipt::class,
                sourceId: $receipt->id,
                branch: $branchId,
                userId: $userId,
                fromSubLedger: true,
            );

            $receipt->update(['gl_journal_id' => $journal->id]);

            return $receipt->fresh('allocations');
        });
    }

    /**
     * Aged debtors as at a date. Returns per-customer buckets plus totals.
     * Buckets: current, 30, 60, 90+ (days past due date).
     */
    public function ageing(string $asOf, ?int $branchId = null): array
    {
        $as = Carbon::parse($asOf);

        $customers = Customer::where('is_walk_in', false)
            ->when($branchId, fn ($q) => $q) // AR is company-level; branch filter reserved
            ->get(['id', 'name', 'customer_number', 'payment_terms_days']);

        $rows = [];
        $totals = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0, 'total' => 0.0];

        foreach ($customers as $customer) {
            $buckets = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0];

            foreach ($this->openInvoices($customer) as $inv) {
                $daysOverdue = Carbon::parse($inv['due_date'])->diffInDays($as, false);
                $amt = $inv['outstanding'];
                if ($daysOverdue <= 0) {
                    $buckets['current'] += $amt;
                } elseif ($daysOverdue <= 30) {
                    $buckets['b30'] += $amt;
                } elseif ($daysOverdue <= 60) {
                    $buckets['b60'] += $amt;
                } else {
                    $buckets['b90'] += $amt;
                }
            }

            // Unapplied credits (unallocated receipts + account credit notes) net down current.
            $unapplied = $this->unappliedCredit($customer);
            $buckets['current'] = round($buckets['current'] - $unapplied, 2);

            $total = round(array_sum($buckets), 2);
            if (abs($total) < 0.005 && $unapplied < 0.005) {
                continue;
            }

            foreach (['current', 'b30', 'b60', 'b90'] as $k) {
                $buckets[$k] = round($buckets[$k], 2);
                $totals[$k] += $buckets[$k];
            }
            $totals['total'] += $total;

            $rows[] = array_merge([
                'customer_id' => $customer->id,
                'customer' => $customer->name,
                'customer_number' => $customer->customer_number,
                'total' => $total,
            ], $buckets);
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return ['as_of' => $as->toDateString(), 'rows' => $rows, 'totals' => $totals];
    }

    /** Unallocated receipts + account-mode credit notes sitting on the account. */
    public function unappliedCredit(Customer $customer): float
    {
        $unallocatedReceipts = (float) CustomerReceipt::where('customer_id', $customer->id)
            ->get()->sum(fn (CustomerReceipt $r) => $r->unallocated());

        $accountCredits = (float) SalesDocument::where('customer_id', $customer->id)
            ->where('document_type', 'credit_note')
            ->where('status', 'posted')
            ->where('credit_mode', 'account')
            ->sum('total_incl');

        return round($unallocatedReceipts + $accountCredits, 2);
    }
}
