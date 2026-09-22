<?php

namespace Tests\Unit;

use App\Exceptions\MissingExchangeRateException;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyService $currency;

    private Currency $usd;

    private Currency $zwg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = app(CurrencyService::class);
        $this->usd = Currency::factory()->base()->create(['code' => 'USD']);
        $this->zwg = Currency::factory()->create(['code' => 'ZWG']);
    }

    public function test_base_currency_rate_is_always_one(): void
    {
        $this->assertSame(1.0, $this->currency->rateFor($this->usd));
        $this->assertSame(1.0, $this->currency->rateFor('USD'));
    }

    public function test_missing_rate_throws_domain_exception(): void
    {
        $this->expectException(MissingExchangeRateException::class);

        $this->currency->rateFor($this->zwg);
    }

    public function test_rate_lookup_uses_latest_rate_on_or_before_date(): void
    {
        $this->currency->captureRate($this->zwg, 26.0, 26.5, '2026-09-01');
        $this->currency->captureRate($this->zwg, 27.0, 27.5, '2026-09-15');
        $this->currency->captureRate($this->zwg, 28.0, 28.5, '2026-09-22');

        // Between 15th and 22nd, the 15th's rate applies.
        $this->assertSame(27.5, $this->currency->rateFor($this->zwg, '2026-09-18'));
        // Exactly the 22nd uses the 22nd's rate.
        $this->assertSame(28.5, $this->currency->rateFor($this->zwg, '2026-09-22'));
        // Buy side.
        $this->assertSame(27.0, $this->currency->rateFor($this->zwg, '2026-09-18', 'buy'));
    }

    public function test_historical_rate_survives_later_captures(): void
    {
        $this->currency->captureRate($this->zwg, 26.0, 26.5, '2026-09-01');
        $this->currency->captureRate($this->zwg, 30.0, 30.5, '2026-09-22');

        // Reprinting a document from the 5th still uses the rate of the 1st.
        $this->assertSame(26.5, $this->currency->rateFor($this->zwg, '2026-09-05'));
    }

    public function test_conversions_round_trip_sensibly(): void
    {
        $this->currency->captureRate($this->zwg, 26.0, 26.5, now());

        // A $100 price displayed in ZWG at sell rate.
        $this->assertSame(2650.0, $this->currency->fromBase(100, $this->zwg));

        // ZiG 2600 cash tendered converts to base at buy rate.
        $this->assertSame(100.0, $this->currency->toBase(2600, $this->zwg));
    }

    public function test_has_rate_for_today(): void
    {
        $this->assertTrue($this->currency->hasRateForToday($this->usd));
        $this->assertFalse($this->currency->hasRateForToday($this->zwg));

        $this->currency->captureRate($this->zwg, 26.0, 26.5, now());

        $this->assertTrue($this->currency->hasRateForToday($this->zwg));
    }

    public function test_capturing_same_day_twice_updates_not_duplicates(): void
    {
        $this->currency->captureRate($this->zwg, 26.0, 26.5, now());
        $this->currency->captureRate($this->zwg, 27.0, 27.5, now());

        $this->assertSame(1, $this->zwg->exchangeRates()->count());
        $this->assertSame(27.5, $this->currency->rateFor($this->zwg));
    }
}
