<?php

namespace App\Services;

use App\Models\CustomerReceipt;
use App\Models\SalesPayment;
use App\Models\User;

/**
 * Daily cash-up / X-Z read (Module 2.4). Summarises a day's takings for a
 * branch by payment method and by cashier, splitting banked money
 * (cash/card/eft) from credit extended (account). Read-only — it reports on
 * documents already posted through the sales/AR services.
 */
class CashupService
{
    private const METHODS = ['cash', 'card', 'eft', 'account'];

    /** @return array<string,mixed> */
    public function forDay(string $date, ?int $branchId = null): array
    {
        // Sale tenders on invoices posted that day.
        $sales = SalesPayment::query()
            ->join('sales_documents as d', 'd.id', '=', 'sales_payments.document_id')
            ->where('d.document_type', 'invoice')
            ->where('d.status', 'posted')
            ->whereDate('d.document_date', $date)
            ->when($branchId, fn ($q) => $q->where('d.branch_id', $branchId))
            ->get(['sales_payments.method', 'sales_payments.amount', 'sales_payments.change_given',
                'sales_payments.received_by', 'd.salesperson_id', 'd.id as document_id']);

        // AR receipts banked that day.
        $receipts = CustomerReceipt::query()
            ->whereDate('receipt_date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['method', 'amount', 'received_by']);

        $names = $this->cashierNames($sales, $receipts);

        $byMethod = [];
        foreach (self::METHODS as $m) {
            $byMethod[$m] = ['method' => $m, 'sales' => 0.0, 'receipts' => 0.0, 'total' => 0.0, 'count' => 0];
        }

        // Per-cashier grid (uses the tender's received_by, else the salesperson).
        $cashiers = [];
        $add = function (int|string|null $uid, string $method, float $amount) use (&$cashiers, $names) {
            $key = $uid ?? 0;
            $cashiers[$key] ??= ['cashier' => $names[$key] ?? 'Unattributed', 'cash' => 0, 'card' => 0, 'eft' => 0, 'account' => 0, 'total' => 0];
            $cashiers[$key][$method] = round($cashiers[$key][$method] + $amount, 2);
            $cashiers[$key]['total'] = round($cashiers[$key]['total'] + $amount, 2);
        };
        foreach ($sales as $p) {
            $m = $this->bucket($p->method);
            $byMethod[$m]['sales'] = round($byMethod[$m]['sales'] + (float) $p->amount, 2);
            $byMethod[$m]['count']++;
            $add($p->received_by ?? $p->salesperson_id, $m, (float) $p->amount);
        }
        foreach ($receipts as $r) {
            $m = $this->bucket($r->method);
            $byMethod[$m]['receipts'] = round($byMethod[$m]['receipts'] + (float) $r->amount, 2);
            $byMethod[$m]['count']++;
            $add($r->received_by, $m, (float) $r->amount);
        }
        foreach ($byMethod as &$row) {
            $row['total'] = round($row['sales'] + $row['receipts'], 2);
        }
        unset($row);

        $banked = round($byMethod['cash']['total'] + $byMethod['card']['total'] + $byMethod['eft']['total'], 2);

        return [
            'date' => $date,
            'by_method' => array_values($byMethod),
            'by_cashier' => array_values($cashiers),
            'totals' => [
                'cash' => $byMethod['cash']['total'],
                'card' => $byMethod['card']['total'],
                'eft' => $byMethod['eft']['total'],
                'account' => $byMethod['account']['total'],
                'banked' => $banked,
                'credit' => $byMethod['account']['total'],
                'change_given' => round((float) $sales->sum('change_given'), 2),
                'grand_total' => round($banked + $byMethod['account']['total'], 2),
                'invoice_count' => $sales->pluck('document_id')->unique()->count(),
                'receipt_count' => $receipts->count(),
            ],
        ];
    }

    /** Fold a raw tender method into one of the four cash-up buckets. */
    private function bucket(?string $method): string
    {
        $method = strtolower((string) $method);
        if ($method === 'bank_transfer') {
            return 'eft';
        }

        return in_array($method, self::METHODS, true) ? $method : 'eft';
    }

    /** Resolve cashier/salesperson display names in one query. */
    private function cashierNames($sales, $receipts): array
    {
        $ids = collect($sales)->flatMap(fn ($p) => [$p->received_by, $p->salesperson_id])
            ->merge(collect($receipts)->pluck('received_by'))
            ->filter()->unique()->values();

        return $ids->isEmpty() ? [] : User::whereIn('id', $ids)->pluck('name', 'id')->all();
    }
}
