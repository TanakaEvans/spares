<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Layby;
use App\Services\NumberSequenceService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LaybyController extends Controller
{
    public function index(SettingsService $settings): Response
    {
        return Inertia::render('Sales/Laybys/Index', [
            'laybys' => Layby::with('customer:id,name')->latest('id')->paginate(20)
                ->through(fn (Layby $l) => [
                    'id' => $l->id, 'layby_number' => $l->layby_number, 'customer' => $l->customer?->name,
                    'total' => (float) $l->total, 'deposit_paid' => (float) $l->deposit_paid, 'balance' => $l->balance(), 'status' => $l->status,
                ]),
            'customers' => Customer::where('is_walk_in', false)->orderBy('name')->limit(200)->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'minDepositPct' => (float) $settings->get('sales.layby_minimum_deposit_pct'),
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
