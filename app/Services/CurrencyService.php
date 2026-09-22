<?php

namespace App\Services;

use App\Exceptions\MissingExchangeRateException;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CurrencyService
{
    private ?Currency $base = null;

    public function base(): Currency
    {
        return $this->base ??= Currency::where('is_base', true)->firstOrFail();
    }

    /**
     * The rate on (or most recently before) the given date.
     * 1 unit of base = rate units of $currency. Base currency always returns 1.0.
     *
     * @param  string  $side  'sell' (pricing display, converting base → foreign)
     *                        or 'buy' (tendering foreign cash, converting foreign → base)
     */
    public function rateFor(Currency|string $currency, CarbonInterface|string|null $date = null, string $side = 'sell'): float
    {
        $currency = $this->resolve($currency);

        if ($currency->is_base) {
            return 1.0;
        }

        $date = $date ? Carbon::parse($date) : Carbon::today();

        $rate = ExchangeRate::where('currency_id', $currency->id)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();

        if ($rate === null) {
            throw new MissingExchangeRateException($currency->code, $date->toDateString());
        }

        return (float) ($side === 'buy' ? $rate->buy_rate : $rate->sell_rate);
    }

    public function hasRateForToday(Currency|string $currency): bool
    {
        $currency = $this->resolve($currency);

        return $currency->is_base || ExchangeRate::where('currency_id', $currency->id)
            ->whereDate('rate_date', Carbon::today())
            ->exists();
    }

    /**
     * Convert a foreign amount into base currency (money received in ZWG → USD books).
     */
    public function toBase(float $amount, Currency|string $currency, CarbonInterface|string|null $date = null, string $side = 'buy'): float
    {
        $rate = $this->rateFor($currency, $date, $side);

        return round($amount / $rate, 2);
    }

    /**
     * Convert a base amount into a foreign currency (USD price shown in ZWG).
     */
    public function fromBase(float $amount, Currency|string $currency, CarbonInterface|string|null $date = null, string $side = 'sell'): float
    {
        $currency = $this->resolve($currency);
        $rate = $this->rateFor($currency, $date, $side);

        return round($amount * $rate, $currency->decimal_places);
    }

    public function captureRate(
        Currency|string $currency,
        float $buyRate,
        float $sellRate,
        CarbonInterface|string|null $date = null,
        ?int $userId = null,
        string $source = 'manual',
    ): ExchangeRate {
        $currency = $this->resolve($currency);
        $rateDate = $date ? Carbon::parse($date) : Carbon::today();

        $rate = ExchangeRate::where('currency_id', $currency->id)
            ->whereDate('rate_date', $rateDate)
            ->first() ?? new ExchangeRate([
                'currency_id' => $currency->id,
                'rate_date' => $rateDate,
            ]);

        $rate->fill([
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'source' => $source,
            'created_by' => $userId,
        ])->save();

        return $rate;
    }

    private function resolve(Currency|string $currency): Currency
    {
        return $currency instanceof Currency
            ? $currency
            : Currency::where('code', strtoupper($currency))->firstOrFail();
    }
}
