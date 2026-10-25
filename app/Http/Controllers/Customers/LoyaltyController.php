<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyEntry;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $balances = DB::table('loyalty_entries')->join('customers', 'customers.id', '=', 'loyalty_entries.customer_id')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('customers.name', 'like', "%{$s}%"))
            ->groupBy('customers.id', 'customers.name')->selectRaw('customers.id, customers.name, SUM(points) as points')
            ->orderByDesc('points')->get()->map(fn ($r) => ['customer_id' => $r->id, 'customer' => $r->name, 'points' => (int) $r->points]);

        return Inertia::render('Customers/Loyalty/Index', [
            'balances' => $balances,
            'recent' => LoyaltyEntry::with('customer:id,name')->latest('id')->limit(30)->get()
                ->map(fn (LoyaltyEntry $e) => ['id' => $e->id, 'customer' => $e->customer?->name, 'customer_id' => $e->customer_id, 'points' => $e->points, 'reason' => $e->reason, 'at' => $e->created_at->toDateString()]),
            'enabled' => (bool) app(SettingsService::class)->get('loyalty.enabled'),
            'earnRate' => (float) app(SettingsService::class)->get('sales.loyalty_earn_rate'),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }
}
