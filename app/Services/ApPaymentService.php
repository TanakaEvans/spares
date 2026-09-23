<?php

namespace App\Services;

use App\Models\PaymentAllocation;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Accounts Payable (Module 7.4): pay suppliers and run batch payments.
 * Posting: DR Trade Creditors control (2110), CR bank/till — through
 * GlPostingService, clearing the AP sub-ledger.
 */
class ApPaymentService
{
    public function __construct(
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    public function invoiceOutstanding(int $invoiceId): float
    {
        $total = (float) SupplierInvoice::whereKey($invoiceId)->value('total');
        $allocated = (float) PaymentAllocation::where('supplier_invoice_id', $invoiceId)->sum('amount');

        return round($total - $allocated, 2);
    }

    /** Open (unpaid) posted supplier invoices, oldest first. */
    public function openInvoices(Supplier $supplier): array
    {
        $invoices = SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('status', 'posted')
            ->orderBy('invoice_date')->orderBy('id')
            ->get(['id', 'invoice_number', 'supplier_ref', 'invoice_date', 'due_date', 'total']);

        $rows = [];
        foreach ($invoices as $inv) {
            $outstanding = $this->invoiceOutstanding($inv->id);
            if ($outstanding <= 0.005) {
                continue;
            }
            $rows[] = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'supplier_ref' => $inv->supplier_ref,
                'invoice_date' => $inv->invoice_date->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'total' => (float) $inv->total,
                'outstanding' => $outstanding,
            ];
        }

        return $rows;
    }

    /**
     * @param array $allocations [['supplier_invoice_id'=>, 'amount'=>], …]
     */
    public function postPayment(
        Supplier $supplier,
        int $branchId,
        string $method,
        float $amount,
        array $allocations = [],
        ?string $reference = null,
        ?string $date = null,
        ?int $userId = null,
        ?string $batchRef = null,
    ): SupplierPayment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }
        if (! in_array($method, ['eft', 'cash', 'cheque', 'bank_transfer'], true)) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        $allocTotal = round(array_sum(array_map(fn ($a) => (float) $a['amount'], $allocations)), 2);
        if ($allocTotal > $amount + 0.009) {
            throw new InvalidArgumentException('Allocations exceed the payment amount.');
        }
        foreach ($allocations as $a) {
            $outstanding = $this->invoiceOutstanding((int) $a['supplier_invoice_id']);
            if ((float) $a['amount'] > $outstanding + 0.009) {
                throw new InvalidArgumentException("Allocation exceeds invoice outstanding {$outstanding}.");
            }
        }

        return DB::transaction(function () use ($supplier, $branchId, $method, $amount, $allocations, $allocTotal, $reference, $date, $userId, $batchRef) {
            $payment = SupplierPayment::create([
                'payment_number' => $this->sequences->next('payment', $branchId),
                'supplier_id' => $supplier->id,
                'branch_id' => $branchId,
                'payment_date' => $date ?? now()->toDateString(),
                'method' => $method,
                'amount' => $amount,
                'allocated' => $allocTotal,
                'reference' => $reference,
                'batch_ref' => $batchRef,
                'paid_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($allocations as $a) {
                if ((float) $a['amount'] <= 0) {
                    continue;
                }
                $payment->allocations()->create([
                    'supplier_invoice_id' => $a['supplier_invoice_id'],
                    'amount' => $a['amount'],
                ]);
            }

            $bankAccount = $method === 'cash' ? '1110' : '1120';
            $journal = $this->gl->post(
                journalType: 'payment',
                date: $payment->payment_date,
                description: "Payment {$payment->payment_number} — {$supplier->name}",
                lines: [
                    ['account' => '2110', 'debit' => $amount, 'credit' => 0, 'description' => 'Creditors settlement'],
                    ['account' => $bankAccount, 'debit' => 0, 'credit' => $amount, 'description' => ucfirst($method).' paid'],
                ],
                reference: $payment->payment_number,
                sourceType: SupplierPayment::class,
                sourceId: $payment->id,
                branch: $branchId,
                userId: $userId,
                fromSubLedger: true,
            );

            $payment->update(['gl_journal_id' => $journal->id]);

            return $payment->fresh('allocations');
        });
    }

    /**
     * Payment run: pay the full outstanding of each selected invoice, one
     * payment per supplier, under a shared batch reference.
     *
     * @param  int[]  $invoiceIds
     * @return array{batch_ref:string, payments:SupplierPayment[]}
     */
    public function runBatch(array $invoiceIds, int $branchId, string $method = 'eft', ?int $userId = null): array
    {
        if ($invoiceIds === []) {
            throw new InvalidArgumentException('Select at least one invoice to pay.');
        }

        $batchRef = 'RUN-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

        $invoices = SupplierInvoice::whereIn('id', $invoiceIds)
            ->where('status', 'posted')
            ->get()
            ->groupBy('supplier_id');

        $payments = [];
        foreach ($invoices as $supplierId => $group) {
            $supplier = Supplier::find($supplierId);
            $allocations = [];
            $total = 0.0;
            foreach ($group as $inv) {
                $outstanding = $this->invoiceOutstanding($inv->id);
                if ($outstanding <= 0.005) {
                    continue;
                }
                $allocations[] = ['supplier_invoice_id' => $inv->id, 'amount' => $outstanding];
                $total += $outstanding;
            }
            if ($total <= 0.005) {
                continue;
            }
            $payments[] = $this->postPayment(
                $supplier, $branchId, $method, round($total, 2), $allocations,
                $batchRef, null, $userId, $batchRef
            );
        }

        return ['batch_ref' => $batchRef, 'payments' => $payments];
    }

    /** Aged creditors as at a date, per supplier. */
    public function ageing(string $asOf): array
    {
        $as = Carbon::parse($asOf);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'supplier_number']);

        $rows = [];
        $totals = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0, 'total' => 0.0];

        foreach ($suppliers as $supplier) {
            $buckets = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0];
            foreach ($this->openInvoices($supplier) as $inv) {
                $due = $inv['due_date'] ?? $inv['invoice_date'];
                $daysOverdue = Carbon::parse($due)->diffInDays($as, false);
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
            $total = round(array_sum($buckets), 2);
            if (abs($total) < 0.005) {
                continue;
            }
            foreach (['current', 'b30', 'b60', 'b90'] as $k) {
                $buckets[$k] = round($buckets[$k], 2);
                $totals[$k] += $buckets[$k];
            }
            $totals['total'] += $total;

            $rows[] = array_merge([
                'supplier_id' => $supplier->id,
                'supplier' => $supplier->name,
                'supplier_number' => $supplier->supplier_number,
                'total' => $total,
            ], $buckets);
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return ['as_of' => $as->toDateString(), 'rows' => $rows, 'totals' => $totals];
    }
}
