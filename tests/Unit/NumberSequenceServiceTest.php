<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class NumberSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private NumberSequenceService $sequences;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequences = app(NumberSequenceService::class);
    }

    private function makeSequence(array $attributes = []): NumberSequence
    {
        return NumberSequence::create(array_merge([
            'type' => 'invoice',
            'branch_id' => null,
            'prefix' => 'INV',
            'include_date' => true,
            'date_format' => 'Ymd',
            'next_number' => 1,
            'padding' => 4,
            'reset_frequency' => 'never',
        ], $attributes));
    }

    public function test_generates_formatted_sequential_numbers(): void
    {
        $this->makeSequence();
        $date = now()->format('Ymd');

        $this->assertSame("INV-{$date}-0001", $this->sequences->next('invoice'));
        $this->assertSame("INV-{$date}-0002", $this->sequences->next('invoice'));
        $this->assertSame("INV-{$date}-0003", $this->sequences->next('invoice'));
    }

    public function test_no_date_segment_when_disabled(): void
    {
        $this->makeSequence(['type' => 'customer', 'prefix' => 'CUST', 'include_date' => false, 'padding' => 5]);

        $this->assertSame('CUST-00001', $this->sequences->next('customer'));
    }

    public function test_peek_does_not_consume(): void
    {
        $this->makeSequence();
        $date = now()->format('Ymd');

        $this->assertSame("INV-{$date}-0001", $this->sequences->peek('invoice'));
        $this->assertSame("INV-{$date}-0001", $this->sequences->peek('invoice'));
        $this->assertSame("INV-{$date}-0001", $this->sequences->next('invoice'));
    }

    public function test_branch_sequence_wins_over_global(): void
    {
        $branch = Branch::factory()->create();
        $this->makeSequence(); // global
        $this->makeSequence(['branch_id' => $branch->id, 'prefix' => 'HRE', 'include_date' => false]);

        $this->assertSame('HRE-0001', $this->sequences->next('invoice', $branch));
        // Global untouched.
        $date = now()->format('Ymd');
        $this->assertSame("INV-{$date}-0001", $this->sequences->next('invoice'));
    }

    public function test_branch_without_own_sequence_falls_back_to_global(): void
    {
        $branch = Branch::factory()->create();
        $this->makeSequence();
        $date = now()->format('Ymd');

        $this->assertSame("INV-{$date}-0001", $this->sequences->next('invoice', $branch));
        $this->assertSame("INV-{$date}-0002", $this->sequences->next('invoice'));
    }

    public function test_unconfigured_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->sequences->next('nonexistent');
    }

    public function test_yearly_reset_restarts_numbering(): void
    {
        $this->makeSequence([
            'type' => 'job_card',
            'prefix' => 'JC',
            'date_format' => 'Y',
            'padding' => 5,
            'reset_frequency' => 'yearly',
            'next_number' => 445,
            'last_reset_at' => now()->subYear()->toDateString(),
        ]);

        $year = now()->format('Y');

        // New year → counter restarts at 1.
        $this->assertSame("JC-{$year}-00001", $this->sequences->next('job_card'));
        $this->assertSame("JC-{$year}-00002", $this->sequences->next('job_card'));
    }

    public function test_no_reset_within_same_year(): void
    {
        $this->makeSequence([
            'type' => 'job_card',
            'prefix' => 'JC',
            'date_format' => 'Y',
            'padding' => 5,
            'reset_frequency' => 'yearly',
            'next_number' => 445,
            'last_reset_at' => now()->startOfYear()->toDateString(),
        ]);

        $year = now()->format('Y');

        $this->assertSame("JC-{$year}-00445", $this->sequences->next('job_card'));
    }

    public function test_rollback_releases_the_number_gaplessly(): void
    {
        $this->makeSequence();
        $date = now()->format('Ymd');

        try {
            \DB::transaction(function () {
                $this->sequences->next('invoice'); // would be 0001
                throw new \RuntimeException('document insert failed');
            });
        } catch (\RuntimeException) {
            // expected
        }

        // The failed transaction released 0001 — no gap.
        $this->assertSame("INV-{$date}-0001", $this->sequences->next('invoice'));
    }
}
