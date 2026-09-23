<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\PartCrossReference;
use App\Models\PartFitment;
use App\Models\PartSupersession;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private VehicleMake $toyota;

    private VehicleModel $hilux;

    private VehicleVariant $gd6;

    private Part $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);

        $this->toyota = VehicleMake::factory()->create(['name' => 'Toyota', 'code' => 'TOY']);
        $this->hilux = VehicleModel::factory()->create(['make_id' => $this->toyota->id, 'name' => 'Hilux']);
        $this->gd6 = VehicleVariant::factory()->create([
            'model_id' => $this->hilux->id, 'name' => '2.8 GD-6', 'engine_code' => '1GD-FTV',
        ]);

        $this->filter = Part::factory()->create([
            'part_number' => '04152-38020',
            'description' => 'Oil Filter GD-6',
        ]);
    }

    // ── Fitment lookup ────────────────────────────────────────────────────

    public function test_fitment_lookup_finds_variant_scoped_part(): void
    {
        PartFitment::create([
            'part_id' => $this->filter->id, 'make_id' => $this->toyota->id,
            'model_id' => $this->hilux->id, 'variant_id' => $this->gd6->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.fitment', [
                'make_id' => $this->toyota->id,
                'model_id' => $this->hilux->id,
                'variant_id' => $this->gd6->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VehicleRef/Fitment/Index')
                ->where('results', fn ($r) => str_contains(json_encode($r), '04152-38020'))
            );
    }

    public function test_make_level_fitment_matches_any_model_of_that_make(): void
    {
        PartFitment::create(['part_id' => $this->filter->id, 'make_id' => $this->toyota->id]);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.fitment', [
                'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id,
            ]))
            ->assertInertia(fn ($page) => $page
                ->where('results', fn ($r) => str_contains(json_encode($r), '04152-38020')));
    }

    public function test_year_outside_fitment_range_excludes_part(): void
    {
        PartFitment::create([
            'part_id' => $this->filter->id, 'make_id' => $this->toyota->id,
            'model_id' => $this->hilux->id, 'year_from' => 2016, 'year_to' => 2020,
        ]);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.fitment', [
                'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year' => 2010,
            ]))
            ->assertInertia(fn ($page) => $page
                ->where('results', fn ($r) => ! str_contains(json_encode($r), '04152-38020')));

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.fitment', [
                'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year' => 2018,
            ]))
            ->assertInertia(fn ($page) => $page
                ->where('results', fn ($r) => str_contains(json_encode($r), '04152-38020')));
    }

    public function test_wrong_make_returns_nothing(): void
    {
        PartFitment::create(['part_id' => $this->filter->id, 'make_id' => $this->toyota->id]);
        $nissan = VehicleMake::factory()->create(['name' => 'Nissan']);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.fitment', ['make_id' => $nissan->id]))
            ->assertInertia(fn ($page) => $page
                ->where('results', fn ($r) => ! str_contains(json_encode($r), '04152-38020')));
    }

    // ── Cross-reference search + supersession ─────────────────────────────

    public function test_search_finds_part_by_cross_reference_number(): void
    {
        PartCrossReference::create([
            'part_id' => $this->filter->id, 'reference_number' => 'Z762', 'type' => 'aftermarket',
        ]);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.cross-ref', ['q' => 'Z762']))
            ->assertInertia(fn ($page) => $page
                ->where('results', fn ($r) => str_contains(json_encode($r), '04152-38020')));
    }

    public function test_superseded_part_resolves_to_replacement(): void
    {
        $old = Part::factory()->create(['part_number' => 'OLD-001', 'is_active' => false]);
        PartSupersession::create([
            'old_part_id' => $old->id, 'new_part_id' => $this->filter->id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('vehicle-ref.cross-ref', ['q' => 'OLD-001']))
            ->assertInertia(fn ($page) => $page
                ->where('results.0.superseded_by.part_number', '04152-38020'));

        // Model-level chain resolution
        $this->assertSame($this->filter->id, $old->resolveCurrent()->id);
    }

    public function test_supersession_chain_follows_multiple_hops(): void
    {
        $a = Part::factory()->create(['part_number' => 'CHAIN-A']);
        $b = Part::factory()->create(['part_number' => 'CHAIN-B']);
        PartSupersession::create(['old_part_id' => $a->id, 'new_part_id' => $b->id, 'is_active' => true]);
        PartSupersession::create(['old_part_id' => $b->id, 'new_part_id' => $this->filter->id, 'is_active' => true]);

        $this->assertSame('04152-38020', $a->resolveCurrent()->part_number);
    }

    // ── Parts CRUD ────────────────────────────────────────────────────────

    public function test_parts_index_searches_by_oem_and_crossref(): void
    {
        $this->filter->update(['oem_number' => '90915-XYZ']);
        PartCrossReference::create([
            'part_id' => $this->filter->id, 'reference_number' => 'GUD-999', 'type' => 'aftermarket',
        ]);

        foreach (['04152', '90915-XYZ', 'GUD-999', 'Oil Filter'] as $term) {
            $this->actingAs($this->admin)
                ->get(route('inventory.parts.index', ['search' => $term]))
                ->assertInertia(fn ($page) => $page
                    ->where('parts.data', fn ($d) => str_contains(json_encode($d), '04152-38020')));
        }
    }

    public function test_part_can_be_created_and_number_must_be_unique(): void
    {
        $payload = [
            'part_number' => 'NEW-001',
            'description' => 'Test Brake Pad',
            'category_id' => $this->filter->category_id,
            'unit_id' => $this->filter->unit_id,
            'is_oem' => false,
            'is_active' => true,
        ];

        $this->actingAs($this->admin)
            ->post(route('inventory.parts.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('parts', ['part_number' => 'NEW-001']);

        $this->actingAs($this->admin)
            ->post(route('inventory.parts.store'), $payload)
            ->assertSessionHasErrors('part_number');
    }

    public function test_part_show_renders_with_relations(): void
    {
        $this->actingAs($this->admin)
            ->get(route('inventory.parts.show', $this->filter))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Parts/Show')
                ->where('part.part_number', '04152-38020'));
    }

    public function test_cross_reference_can_be_added_and_duplicates_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.parts.crossrefs.store', $this->filter), [
                'reference_number' => 'HU7019Z', 'type' => 'aftermarket',
            ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('inventory.parts.crossrefs.store', $this->filter), [
                'reference_number' => 'HU7019Z', 'type' => 'aftermarket',
            ])->assertSessionHasErrors('reference_number');
    }

    public function test_marking_superseded_deactivates_old_part(): void
    {
        $new = Part::factory()->create(['part_number' => 'NEWER-01']);

        $this->actingAs($this->admin)
            ->post(route('inventory.parts.supersession.store', $this->filter), [
                'new_part_number' => 'NEWER-01', 'reason' => 'Number change',
            ])->assertRedirect();

        $this->filter->refresh();
        $this->assertFalse($this->filter->is_active);
        $this->assertTrue($this->filter->is_discontinued);
        $this->assertSame($new->id, $this->filter->supersededBy->new_part_id);
    }

    public function test_part_cannot_supersede_itself(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.parts.supersession.store', $this->filter), [
                'new_part_number' => $this->filter->part_number,
            ])->assertSessionHasErrors('new_part_number');
    }

    // ── Reference pages render ────────────────────────────────────────────

    public function test_reference_pages_render(): void
    {
        foreach (['vehicle-ref.makes.index', 'vehicle-ref.models.index', 'vehicle-ref.engines.index',
            'inventory.categories.index', 'inventory.brands.index', 'inventory.bins.index',
            'inventory.stock.index'] as $routeName) {
            $this->actingAs($this->admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_palette_finds_parts(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/search?q=04152');

        $parts = collect($response->json('groups'))->firstWhere('group', 'Parts')['items'] ?? [];
        $this->assertNotEmpty($parts);
        $this->assertStringContainsString('04152-38020', $parts[0]['label']);
    }
}
