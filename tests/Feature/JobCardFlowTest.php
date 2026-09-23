<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\LabourCode;
use App\Models\Part;
use App\Models\StockLevel;
use App\Models\Technician;
use App\Services\GlPostingService;
use App\Services\JobCardService;
use App\Services\StockLedgerService;
use App\Services\WorkshopInvoiceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * THE workshop flow: open a job → labour + issue parts → complete → invoice.
 * Proves parts leave stock at issue (COGS then), the invoice recognises
 * revenue only (parts 4100 + labour 4300 + VAT), and job costing/margin.
 */
class JobCardFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $customer;

    private CustomerVehicle $vehicle;

    private Part $part;

    private Technician $tech;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'Z762', 'description' => 'Oil Filter']);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 20, 3.20, 'Test', 1);

        $this->customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'payment_terms_days' => 30, 'credit_limit' => 5000,
        ]);
        $this->vehicle = CustomerVehicle::create([
            'customer_id' => $this->customer->id, 'registration' => 'ABC-1234', 'year' => 2018,
        ]);
        $this->tech = Technician::create(['name' => 'Tendai', 'skill_level' => 'qualified', 'cost_rate' => 8.00]);
    }

    private function fullJob(): JobCard
    {
        $svc = app(JobCardService::class);

        $job = $svc->create($this->branch->id, [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'reported_fault' => 'Service due',
            'odometer_in' => 84000,
        ]);
        $svc->assignTechnician($job, $this->tech->id); // → allocated
        $svc->transition($job->fresh(), 'in_progress');

        // Labour: 1.5h @ 25.00 = 37.50 billed, cost 1.5 × 8 = 12.00.
        $svc->addLabour($job->fresh(), ['description' => 'Minor service', 'hours' => 1.5, 'rate' => 25.00]);

        // Part: request then issue 2 @ billed 6.40 (cost AVCO 3.20).
        $line = $svc->requestPart($job->fresh(), ['part_id' => $this->part->id, 'qty' => 2, 'unit_price' => 6.40]);
        $svc->issuePart($line->fresh());

        $svc->transition($job->fresh(), 'quality_check');
        $svc->transition($job->fresh(), 'completed');

        return $job->fresh(['labours', 'parts']);
    }

    public function test_issuing_parts_moves_stock_and_books_cogs(): void
    {
        $gl = app(GlPostingService::class);
        $this->fullJob();

        // Stock 20 → 18.
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(18, (float) $level->qty_on_hand, 0.001);

        // COGS booked at issue: 2 × 3.20 = 6.40.
        $this->assertEqualsWithDelta(6.40, $gl->accountBalance('5100'), 0.001);
        $this->assertCount(0, app(StockLedgerService::class)->verifyIntegrity($this->branch->id));
    }

    public function test_completed_job_invoices_revenue_only_with_labour_split(): void
    {
        $gl = app(GlPostingService::class);
        $job = $this->fullJob();

        $invoice = app(WorkshopInvoiceService::class)->invoiceJob($job, []); // on account

        // Parts 12.80 + labour 37.50 = 50.30 excl; VAT 15% = 7.545 → 7.55 (rounded per line: 1.92 + 5.63 = 7.55). Total 57.85.
        $this->assertEqualsWithDelta(50.30, (float) $invoice->subtotal_excl, 0.001);
        $this->assertEqualsWithDelta(57.85, (float) $invoice->total_incl, 0.02);

        // Revenue split: parts 4100 = 12.80, labour 4300 = 37.50.
        $this->assertEqualsWithDelta(12.80, $gl->accountBalance('4100'), 0.001);
        $this->assertEqualsWithDelta(37.50, $gl->accountBalance('4300'), 0.001);

        // COGS unchanged by invoicing (still just the issue's 6.40).
        $this->assertEqualsWithDelta(6.40, $gl->accountBalance('5100'), 0.001);

        // Job linked + invoiced; service history written.
        $this->assertSame('invoiced', $job->fresh()->status);
        $this->assertSame($invoice->id, $job->fresh()->sales_document_id);
        $this->assertDatabaseHas('vehicle_service_history', ['vehicle_id' => $this->vehicle->id, 'job_card_id' => $job->id]);

        // Labour line carries no part.
        $this->assertNull($invoice->lines->firstWhere('line_type', 'labour')->part_id);
    }

    public function test_job_costing_margin(): void
    {
        $job = $this->fullJob();

        // Billed: parts 12.80 + labour 37.50 = 50.30. Cost: parts 6.40 + labour 12.00 = 18.40.
        $this->assertEqualsWithDelta(50.30, $job->totalBilled(), 0.001);
        $this->assertEqualsWithDelta(18.40, $job->totalCost(), 0.001);
        // Margin = (50.30 − 18.40) / 50.30 = 63.4%.
        $this->assertEqualsWithDelta(63.4, $job->marginPct(), 0.1);
    }

    public function test_returned_part_reverses_cost_and_is_not_billed(): void
    {
        $gl = app(GlPostingService::class);
        $svc = app(JobCardService::class);
        $job = $this->fullJob();

        $partLine = $job->parts->first();
        $svc->returnPart($partLine->fresh());

        // Stock back to 20, COGS reversed to 0.
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(20, (float) $level->qty_on_hand, 0.001);
        $this->assertEqualsWithDelta(0, $gl->accountBalance('5100'), 0.001);

        // Invoice now bills labour only.
        $invoice = app(WorkshopInvoiceService::class)->invoiceJob($job->fresh(['labours', 'parts']), []);
        $this->assertEqualsWithDelta(37.50, (float) $invoice->subtotal_excl, 0.001);
        $this->assertEqualsWithDelta(0, $gl->accountBalance('4100'), 0.001);
    }

    public function test_cannot_allocate_without_customer_and_vehicle(): void
    {
        $job = app(JobCardService::class)->create($this->branch->id, ['reported_fault' => 'Noise']);
        $this->expectException(\InvalidArgumentException::class);
        app(JobCardService::class)->transition($job, 'allocated');
    }

    public function test_labour_uses_make_specific_flat_rate(): void
    {
        $make = \App\Models\VehicleMake::create(['name' => 'Toyota', 'code' => 'TOY']);
        $vehicle = CustomerVehicle::create(['customer_id' => $this->customer->id, 'registration' => 'TOY-1', 'make_id' => $make->id]);
        $code = LabourCode::create(['code' => 'ENG-OIL-01', 'description' => 'Oil change', 'default_rate' => 20.00]);
        $code->rates()->create(['make_id' => $make->id, 'flat_rate' => 30.00]);

        $svc = app(JobCardService::class);
        $job = $svc->create($this->branch->id, ['customer_id' => $this->customer->id, 'vehicle_id' => $vehicle->id]);
        $svc->addLabour($job->fresh(), ['labour_code_id' => $code->id, 'hours' => 1]);

        // Make-specific 30.00 wins over default 20.00.
        $this->assertEqualsWithDelta(30.00, (float) $job->fresh()->labours->first()->rate, 0.001);
    }
}
