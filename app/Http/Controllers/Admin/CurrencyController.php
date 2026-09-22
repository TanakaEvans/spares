<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function __construct(private readonly CurrencyService $currency)
    {
    }

    public function index(): Response
    {
        $currencies = Currency::orderByDesc('is_base')->orderBy('code')->get()
            ->map(function (Currency $c) {
                $latest = $c->exchangeRates()->orderByDesc('rate_date')->first();

                return [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'symbol' => $c->symbol,
                    'decimal_places' => $c->decimal_places,
                    'is_base' => $c->is_base,
                    'is_active' => $c->is_active,
                    'latest_rate' => $latest ? [
                        'date' => $latest->rate_date->toDateString(),
                        'buy' => (float) $latest->buy_rate,
                        'sell' => (float) $latest->sell_rate,
                        'is_today' => $latest->rate_date->isToday(),
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Currencies/Index', [
            'currencies' => $currencies,
            'rateHistory' => ExchangeRate::with('currency:id,code')
                ->orderByDesc('rate_date')
                ->limit(30)
                ->get()
                ->map(fn (ExchangeRate $r) => [
                    'id' => $r->id,
                    'currency' => $r->currency->code,
                    'date' => $r->rate_date->toDateString(),
                    'buy' => (float) $r->buy_rate,
                    'sell' => (float) $r->sell_rate,
                    'source' => $r->source,
                ]),
        ]);
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'buy_rate' => ['required', 'numeric', 'gt:0'],
            'sell_rate' => ['required', 'numeric', 'gt:0', 'gte:buy_rate'],
            'rate_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $currency = Currency::findOrFail($data['currency_id']);

        if ($currency->is_base) {
            return back()->withErrors(['currency_id' => 'The base currency does not take exchange rates.']);
        }

        $this->currency->captureRate(
            $currency,
            (float) $data['buy_rate'],
            (float) $data['sell_rate'],
            $data['rate_date'],
            $request->user()->id,
        );

        return back()->with('success', "Rate captured for {$currency->code} ({$data['rate_date']}).");
    }

    public function toggleActive(Currency $currency): RedirectResponse
    {
        if ($currency->is_base) {
            return back()->withErrors(['currency' => 'The base currency cannot be deactivated.']);
        }

        $currency->update(['is_active' => ! $currency->is_active]);

        return back()->with('success', "{$currency->code} ".($currency->is_active ? 'activated' : 'deactivated').'.');
    }
}
