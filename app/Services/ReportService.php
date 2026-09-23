<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\JobCard;
use App\Models\PurchaseOrder;
use App\Models\SalesDocument;
use App\Models\StockLevel;
use App\Models\SupplierInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only analytics over the live ledger and sub-ledgers (Module 9).
 * Never caches — every figure is derived at query time so reports can't drift.
 */
class ReportService
{
    public function __construct(
        private readonly ArReceiptService $ar,
        private readonly ApPaymentService $ap,
        private readonly LedgerReportService $ledger,
    ) {
    }

    // ── 9.1 Executive dashboard ──────────────────────────────────────────

    public function kpis(?int $branchId = null): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $ageing = $this->ar->ageing($today);

        return [
            'today_sales' => $this->netSales($today, $today, $branchId),
            'mtd_sales' => $this->netSales($monthStart, $today, $branchId),
            'outstanding_ar' => $ageing['totals']['total'],
            'overdue_ar_60' => round($ageing['totals']['b60'] + $ageing['totals']['b90'], 2),
            'stock_value' => $this->stockValue($branchId)['total'],
            'open_jobs' => JobCard::whereNotIn('status', ['invoiced', 'closed', 'cancelled'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
        ];
    }

    /** Net sales (invoices − credit notes) in a date range. */
    public function netSales(string $from, string $to, ?int $branchId = null): float
    {
        $inv = (float) SalesDocument::where('document_type', 'invoice')->where('status', 'posted')
            ->whereDate('document_date', '>=', $from)->whereDate('document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_incl');
        $cn = (float) SalesDocument::where('document_type', 'credit_note')->where('status', 'posted')
            ->whereDate('document_date', '>=', $from)->whereDate('document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_incl');

        return round($inv - $cn, 2);
    }

    /** 12-month sales trend: this year vs last year, by calendar month. */
    public function salesTrend(?int $branchId = null): array
    {
        $year = (int) now()->year;
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $label = Carbon::create($year, $m, 1)->format('M');
            $rows[] = [
                'month' => $label,
                'this_year' => $this->monthSales($year, $m, $branchId),
                'last_year' => $this->monthSales($year - 1, $m, $branchId),
            ];
        }

        return $rows;
    }

    private function monthSales(int $year, int $month, ?int $branchId): float
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return $this->netSales($start->toDateString(), $start->copy()->endOfMonth()->toDateString(), $branchId);
    }

    /** AR ageing profile for the dashboard chart. */
    public function arAgeingProfile(): array
    {
        $t = $this->ar->ageing(now()->toDateString())['totals'];

        return [
            ['bucket' => 'Current', 'value' => $t['current']],
            ['bucket' => '30', 'value' => $t['b30']],
            ['bucket' => '60', 'value' => $t['b60']],
            ['bucket' => '90+', 'value' => $t['b90']],
        ];
    }

    // ── Sales analytics (9.2) ────────────────────────────────────────────

    public function topPartsByValue(string $from, string $to, int $limit = 10, ?int $branchId = null): array
    {
        return DB::table('sales_document_lines as l')
            ->join('sales_documents as d', 'd.id', '=', 'l.document_id')
            ->join('parts as p', 'p.id', '=', 'l.part_id')
            ->where('d.document_type', 'invoice')->where('d.status', 'posted')
            ->where('l.line_type', 'part')
            ->whereDate('d.document_date', '>=', $from)->whereDate('d.document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('d.branch_id', $branchId))
            ->groupBy('p.id', 'p.part_number', 'p.description')
            ->selectRaw('p.part_number, p.description, SUM(l.line_total_excl) as revenue, SUM(l.qty) as qty')
            ->orderByDesc('revenue')->limit($limit)->get()
            ->map(fn ($r) => ['part_number' => $r->part_number, 'description' => $r->description,
                'revenue' => round((float) $r->revenue, 2), 'qty' => (float) $r->qty])->all();
    }

    public function salesByCategory(string $from, string $to, ?int $branchId = null): array
    {
        return DB::table('sales_document_lines as l')
            ->join('sales_documents as d', 'd.id', '=', 'l.document_id')
            ->join('parts as p', 'p.id', '=', 'l.part_id')
            ->leftJoin('part_categories as c', 'c.id', '=', 'p.category_id')
            ->where('d.document_type', 'invoice')->where('d.status', 'posted')
            ->where('l.line_type', 'part')
            ->whereDate('d.document_date', '>=', $from)->whereDate('d.document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('d.branch_id', $branchId))
            ->groupBy('c.name')
            ->selectRaw('COALESCE(c.name, \'Uncategorised\') as category, SUM(l.line_total_excl) as revenue')
            ->orderByDesc('revenue')->get()
            ->map(fn ($r) => ['category' => $r->category, 'revenue' => round((float) $r->revenue, 2)])->all();
    }

    public function salesSummary(string $from, string $to, ?int $branchId = null): array
    {
        $invoices = SalesDocument::where('document_type', 'invoice')->where('status', 'posted')
            ->whereDate('document_date', '>=', $from)->whereDate('document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $count = (clone $invoices)->count();
        $gross = (float) (clone $invoices)->sum('total_incl');
        $vat = (float) (clone $invoices)->sum('vat_amount');

        $byMethod = DB::table('sales_payments as sp')
            ->join('sales_documents as d', 'd.id', '=', 'sp.document_id')
            ->where('d.document_type', 'invoice')->where('d.status', 'posted')
            ->whereDate('d.document_date', '>=', $from)->whereDate('d.document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('d.branch_id', $branchId))
            ->groupBy('sp.method')
            ->selectRaw('sp.method, SUM(sp.amount) as total')->get()
            ->map(fn ($r) => ['method' => $r->method, 'total' => round((float) $r->total, 2)])->all();

        return [
            'count' => $count,
            'gross' => round($gross, 2),
            'vat' => round($vat, 2),
            'net_of_vat' => round($gross - $vat, 2),
            'atv' => $count > 0 ? round($gross / $count, 2) : 0.0,
            'by_method' => $byMethod,
            'top_parts' => $this->topPartsByValue($from, $to, 5, $branchId),
        ];
    }

    public function salesByCustomer(string $from, string $to, int $limit = 20, ?int $branchId = null): array
    {
        return DB::table('sales_documents as d')
            ->join('customers as c', 'c.id', '=', 'd.customer_id')
            ->where('d.document_type', 'invoice')->where('d.status', 'posted')
            ->whereDate('d.document_date', '>=', $from)->whereDate('d.document_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('d.branch_id', $branchId))
            ->groupBy('c.id', 'c.name')
            ->selectRaw('c.id, c.name, COUNT(*) as invoices, SUM(d.total_incl) as spend')
            ->orderByDesc('spend')->limit($limit)->get()
            ->map(fn ($r) => ['customer_id' => $r->id, 'customer' => $r->name,
                'invoices' => (int) $r->invoices, 'spend' => round((float) $r->spend, 2)])->all();
    }

    // ── Inventory analytics (9.3) ────────────────────────────────────────

    public function stockValue(?int $branchId = null): array
    {
        $rows = StockLevel::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $total = round($rows->sum(fn (StockLevel $s) => (float) $s->qty_on_hand * (float) $s->average_cost), 2);
        $gl = $this->ledger->balancesByAccount(null, now()->toDateString());
        $glInventory = null;
        $acc = \App\Models\GlAccount::where('account_code', '1310')->first();
        if ($acc && isset($gl[$acc->id])) {
            $glInventory = round($gl[$acc->id]['debit'] - $gl[$acc->id]['credit'], 2);
        }

        return ['total' => $total, 'gl_1310' => $glInventory, 'reconciled' => $glInventory !== null && abs($glInventory - $total) < 0.01];
    }

    public function stockOnHand(?int $branchId = null, ?string $filter = null): array
    {
        return StockLevel::with('part:id,part_number,description', 'branch:id,name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($filter === 'below_reorder', fn ($q) => $q->whereColumn('qty_on_hand', '<=', 'reorder_point')->where('reorder_point', '>', 0))
            ->when($filter === 'out', fn ($q) => $q->where('qty_on_hand', '<=', 0))
            ->when($filter === 'negative', fn ($q) => $q->where('qty_on_hand', '<', 0))
            ->orderByDesc(DB::raw('qty_on_hand * average_cost'))
            ->limit(300)->get()
            ->map(fn (StockLevel $s) => [
                'part_number' => $s->part?->part_number,
                'description' => $s->part?->description,
                'branch' => $s->branch?->name,
                'on_hand' => (float) $s->qty_on_hand,
                'reserved' => (float) $s->qty_reserved,
                'available' => round((float) $s->qty_on_hand - (float) $s->qty_reserved, 2),
                'avco' => (float) $s->average_cost,
                'value' => round((float) $s->qty_on_hand * (float) $s->average_cost, 2),
                'reorder_point' => (float) $s->reorder_point,
                'below_reorder' => $s->reorder_point > 0 && $s->qty_on_hand <= $s->reorder_point,
            ])->all();
    }

    public function stockAgeing(?int $branchId = null): array
    {
        $now = now();
        $brackets = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '91-180' => 0.0, '180+' => 0.0, 'no_movement' => 0.0];

        StockLevel::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('qty_on_hand', '>', 0)->get()
            ->each(function (StockLevel $s) use (&$brackets, $now) {
                $value = (float) $s->qty_on_hand * (float) $s->average_cost;
                if ($s->last_movement_at === null) {
                    $brackets['no_movement'] += $value;

                    return;
                }
                $days = $s->last_movement_at->diffInDays($now);
                $key = $days <= 30 ? '0-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : ($days <= 180 ? '91-180' : '180+')));
                $brackets[$key] += $value;
            });

        return array_map(fn ($k, $v) => ['bracket' => $k, 'value' => round($v, 2)], array_keys($brackets), $brackets);
    }

    // ── Customer analytics (9.5) ─────────────────────────────────────────

    public function topCustomers(string $from, string $to, int $limit = 15): array
    {
        return $this->salesByCustomer($from, $to, $limit);
    }

    public function dormantCustomers(int $days): array
    {
        $cutoff = now()->subDays($days)->toDateString();

        return Customer::where('is_walk_in', false)->active()
            ->whereDoesntHave('documents', fn ($q) => $q
                ->where('document_type', 'invoice')->where('status', 'posted')
                ->whereDate('document_date', '>=', $cutoff))
            ->whereHas('documents', fn ($q) => $q->where('document_type', 'invoice')->where('status', 'posted'))
            ->orderBy('name')->limit(200)->get(['id', 'name', 'customer_number', 'phone'])
            ->map(fn (Customer $c) => [
                'customer_id' => $c->id, 'customer' => $c->name,
                'customer_number' => $c->customer_number, 'phone' => $c->phone,
                'last_invoice' => optional($c->documents()->where('document_type', 'invoice')->where('status', 'posted')->max('document_date'))
                    ? Carbon::parse($c->documents()->where('document_type', 'invoice')->where('status', 'posted')->max('document_date'))->toDateString()
                    : null,
            ])->all();
    }

    public function creditReview(): array
    {
        return Customer::where('is_walk_in', false)->where('credit_limit', '>', 0)->get()
            ->map(function (Customer $c) {
                $balance = $c->arBalance();
                $util = $c->credit_limit > 0 ? round($balance / (float) $c->credit_limit * 100, 1) : 0;

                return [
                    'customer_id' => $c->id, 'customer' => $c->name,
                    'credit_limit' => (float) $c->credit_limit, 'balance' => $balance,
                    'utilisation' => $util, 'on_hold' => $c->on_hold,
                ];
            })
            ->filter(fn ($r) => $r['utilisation'] >= 80 || $r['on_hold'])
            ->sortByDesc('utilisation')->values()->all();
    }

    // ── Supplier analytics (9.6) ─────────────────────────────────────────

    public function supplierSpend(string $from, string $to): array
    {
        return DB::table('supplier_invoices as si')
            ->join('suppliers as s', 's.id', '=', 'si.supplier_id')
            ->where('si.status', 'posted')
            ->whereDate('si.invoice_date', '>=', $from)->whereDate('si.invoice_date', '<=', $to)
            ->groupBy('s.id', 's.name')
            ->selectRaw('s.id, s.name, COUNT(*) as invoices, SUM(si.total) as spend')
            ->orderByDesc('spend')->get()
            ->map(fn ($r) => ['supplier_id' => $r->id, 'supplier' => $r->name,
                'invoices' => (int) $r->invoices, 'spend' => round((float) $r->spend, 2)])->all();
    }

    public function openPurchaseOrders(): array
    {
        return PurchaseOrder::with('supplier:id,name', 'branch:id,name')
            ->whereIn('status', ['submitted', 'confirmed', 'partial'])
            ->orderBy('expected_date')->get()
            ->map(fn (PurchaseOrder $po) => [
                'id' => $po->id, 'po_number' => $po->po_number,
                'supplier' => $po->supplier?->name, 'branch' => $po->branch?->name,
                'order_date' => $po->order_date->toDateString(),
                'expected_date' => $po->expected_date?->toDateString(),
                'total' => (float) $po->total, 'status' => $po->status,
                'overdue' => $po->expected_date !== null && $po->expected_date->isPast(),
            ])->all();
    }

    // ── Workshop analytics (9.7) ─────────────────────────────────────────

    public function workshopProductivity(string $from, string $to, ?int $branchId = null): array
    {
        $base = JobCard::when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $opened = (clone $base)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
        $completed = (clone $base)->whereNotNull('completed_at')->whereBetween('completed_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
        $invoicedJobs = (clone $base)->where('status', 'invoiced')->whereNotNull('invoiced_at')
            ->whereBetween('invoiced_at', [$from.' 00:00:00', $to.' 23:59:59'])->with('invoice')->get();

        $revenue = round($invoicedJobs->sum(fn (JobCard $j) => (float) ($j->invoice?->total_incl ?? 0)), 2);

        return [
            'opened' => $opened,
            'completed' => $completed,
            'invoiced' => $invoicedJobs->count(),
            'revenue' => $revenue,
            'avg_job_value' => $invoicedJobs->count() > 0 ? round($revenue / $invoicedJobs->count(), 2) : 0.0,
        ];
    }

    public function jobProfitability(string $from, string $to, ?int $branchId = null): array
    {
        return JobCard::with('labours', 'parts', 'customer:id,name')
            ->where('status', 'invoiced')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('invoiced_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->latest('invoiced_at')->limit(200)->get()
            ->map(fn (JobCard $j) => [
                'job_number' => $j->job_number, 'customer' => $j->customer?->name,
                'cost' => $j->totalCost(), 'billed' => $j->totalBilled(), 'margin_pct' => $j->marginPct(),
            ])->all();
    }
}
