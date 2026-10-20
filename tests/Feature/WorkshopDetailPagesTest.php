<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Services\JobCardService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The Workshop detail pages render and carry the ids every entity link needs
 * (UI-13): technician profile, warranty claim, and the job-card hub.
 */
class WorkshopDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    private Customer $customer;

    private CustomerVehicle $vehicle;

    private Technician $tech;

    private JobCard $job;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);

        $this->branch = Branch::factory()->create();
        $this->customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'payment_terms_days' => 30, 'credit_limit' => 5000,
        ]);
        $this->vehicle = CustomerVehicle::create([
            'customer_id' => $this->customer->id, 'registration' => 'ABC-1234', 'year' => 2018,
        ]);
        $this->tech = Technician::create(['name' => 'Tendai', 'skill_level' => 'qualified', 'cost_rate' => 8.00]);

        $svc = app(JobCardService::class);
        $this->job = $svc->create($this->branch->id, [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'reported_fault' => 'Service due',
        ], $this->user->id);
        $svc->assignTechnician($this->job, $this->tech->id);
        $svc->addLabour($this->job->fresh(), ['description' => 'Minor service', 'hours' => 1.5, 'rate' => 25.00, 'technician_id' => $this->tech->id]);
    }

    public function test_technician_profile_renders_with_linked_jobs(): void
    {
        $this->actingAs($this->user)
            ->get(route('workshop.technicians.show', $this->tech))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Workshop/Technicians/Show')
                ->where('technician.id', $this->tech->id)
                ->where('technician.name', 'Tendai')
                ->has('jobs', 1, fn (Assert $job) => $job
                    ->where('id', $this->job->id)
                    ->where('customer_id', $this->customer->id)
                    ->where('vehicle_id', $this->vehicle->id)
                    ->etc())
                ->where('productivity.jobs_total', 1)
                ->where('productivity.hours_booked', 1.5)
            );
    }

    public function test_warranty_claim_detail_renders_with_job_links(): void
    {
        $claim = WarrantyClaim::create([
            'claim_number' => 'WC-0001', 'job_card_id' => $this->job->id, 'status' => 'draft',
            'fault' => 'Seal failed within 30 days', 'claim_amount' => 120.00,
        ]);

        $this->actingAs($this->user)
            ->get(route('workshop.warranty.show', $claim))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Workshop/Warranty/Show')
                ->where('claim.claim_number', 'WC-0001')
                ->where('claim.job_card_id', $this->job->id)
                ->where('claim.customer_id', $this->customer->id)
                ->where('claim.vehicle_id', $this->vehicle->id)
                ->where('claim.claim_amount', fn ($v) => (float) $v === 120.0)
            );
    }

    public function test_job_card_hub_renders_with_entity_ids_and_claims(): void
    {
        WarrantyClaim::create([
            'claim_number' => 'WC-0002', 'job_card_id' => $this->job->id, 'status' => 'submitted',
            'fault' => 'Bearing noise', 'claim_amount' => 45.00,
        ]);

        $this->actingAs($this->user)
            ->get(route('workshop.jobs.show', $this->job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Workshop/Jobs/Show')
                ->where('job.customer.id', $this->customer->id)
                ->where('job.vehicle.id', $this->vehicle->id)
                ->where('job.technician.id', $this->tech->id)
                ->where('job.invoice', null)
                ->has('job.warranty_claims', 1, fn (Assert $c) => $c->where('claim_number', 'WC-0002')->etc())
                ->has('job.labours', 1, fn (Assert $l) => $l->where('technician_id', $this->tech->id)->etc())
            );
    }
}
