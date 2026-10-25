<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Layby;
use App\Services\NumberSequenceService;
use App\Services\SettingsService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LaybyController extends Controller
{
    use ListQuery;

    private function laybysQuery(Request $request): Builder
    {
        return $this->listApply(
            Layby::with('customer:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['layby_number', 'notes', 'customer.name'],
                'sortable' => [
                    'layby_number' => 'layby_number', 'created_at' => 'created_at',
                    'total' => 'total', 'deposit_paid' => 'deposit_paid', 'status' => 'status',
                ],
                'dateColumn' => 'created_at',
                'defaultSort' => ['id' => 'desc'],
            ]
        );
    }

    /** The filtered lay-by list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->laybysQuery($request)->limit(5000)->get()->map(fn (Layby $l) => [
            $l->layby_number, $l->customer?->name, $l->created_at?->toDateString(),
            ucwords($l->status),
            number_format((float) $l->total, 2, '.', ''),
            number_format((float) $l->deposit_paid, 2, '.', ''),
            number_format($l->balance(), 2, '.', ''),
        ]);

        return ListExport::respond($request, 'laybys', 'Lay-bys',
            ['Lay-by No', 'Customer', 'Started', 'Status', 'Total', 'Paid', 'Balance'], $rows);
    }

    public function index(Request $request, SettingsService $settings): Response
    {
        return Inertia::render('Sales/Laybys/Index', [
            'laybys' => $this->laybysQuery($request)->paginate(20)
                ->withQueryString()
                ->through(fn (Layby $l) => [
                    'id' => $l->id, 'layby_number' => $l->layby_number,
                    'customer' => $l->customer?->name, 'customer_id' => $l->customer_id,
                    'total' => (float) $l->total, 'deposit_paid' => (float) $l->deposit_paid, 'balance' => $l->balance(), 'status' => $l->status,
                ]),
            'customers' => Customer::where('is_walk_in', false)->orderBy('name')->limit(200)->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'minDepositPct' => (float) $settings->get('sales.layby_minimum_deposit_pct'),
            'stats' => [
                'active' => (int) Layby::where('status', 'active')->count(),
                'outstanding' => round((float) Layby::where('status', 'active')->selectRaw('COALESCE(SUM(total - deposit_paid), 0) outstanding')->value('outstanding'), 2),
            ],
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function show(Layby $layby): Response
    {
        $layby->load('customer:id,name,customer_number', 'branch:id,name', 'payments');

        return Inertia::render('Sales/Laybys/Show', [
            'layby' => [
                'id' => $layby->id,
                'layby_number' => $layby->layby_number,
                'status' => $layby->status,
                'customer' => $layby->customer?->only(['id', 'name', 'customer_number']),
                'branch' => $layby->branch?->only(['id', 'name']),
                'total' => (float) $layby->total,
                'deposit_paid' => (float) $layby->deposit_paid,
                'balance' => $layby->balance(),
                'notes' => $layby->notes,
                'created_at' => $layby->created_at?->toDateString(),
                'payments' => $layby->payments->sortByDesc('id')->values()->map(fn ($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'method' => $p->method,
                    'paid_on' => $p->paid_on?->toDateString(),
                ]),
            ],
        ]);
    }

    public function store(Request $request, NumberSequenceService $seq): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'], 'branch_id' => ['required', 'exists:branches,id'],
            'total' => ['required', 'numeric', 'gt:0'], 'deposit' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($data, $seq) {
            $layby = Layby::create([
                'layby_number' => $seq->next('layby', $data['branch_id']), 'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'], 'total' => $data['total'], 'deposit_paid' => $data['deposit'],
                'status' => 'active', 'notes' => $data['notes'] ?? null,
            ]);
            if ($data['deposit'] > 0) {
                $layby->payments()->create(['amount' => $data['deposit'], 'method' => 'cash', 'paid_on' => now()->toDateString()]);
            }
        });

        return back()->with('success', 'Lay-by created.');
    }

    public function addPayment(Request $request, Layby $layby): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'in:cash,card,eft']]);
        DB::transaction(function () use ($layby, $data) {
            $layby->payments()->create($data + ['paid_on' => now()->toDateString()]);
            $paid = round((float) $layby->deposit_paid + $data['amount'], 2);
            $layby->update(['deposit_paid' => $paid, 'status' => $paid >= (float) $layby->total ? 'completed' : 'active']);
        });

        return back()->with('success', 'Payment recorded.');
    }
}
