<?php

namespace Tests\Feature;

use App\Models\EngineCode;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vehicle reference master data: detail pages show their contents, edits
 * stick, and deletes never orphan a fitment — in-use records are retired.
 */
class VehicleReferenceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private VehicleMake $toyota;

    private VehicleModel $hilux;

    private VehicleVariant $gd6;

    private EngineCode $engine;

    private Part $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);
        $this->actingAs($this->admin);

        $this->toyota = VehicleMake::factory()->create(['name' => 'Toyota', 'code' => 'TOY']);
        $this->hilux = VehicleModel::factory()->create(['make_id' => $this->toyota->id, 'name' => 'Hilux']);
        $this->gd6 = VehicleVariant::factory()->create(['model_id' => $this->hilux->id, 'name' => '2.8 GD-6', 'engine_code' => '1GD-FTV']);
        $this->engine = EngineCode::create(['code' => '1GD-FTV', 'make_id' => $this->toyota->id, 'description' => '2.8 diesel']);
        $this->filter = Part::factory()->create(['part_number' => '04152-38020', 'description' => 'Oil Filter GD-6']);
        PartFitment::create(['part_id' => $this->filter->id, 'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'engine_code' => '1GD-FTV']);
    }

    public function test_make_detail_shows_models_engines_and_fitted_parts(): void
    {
        $this->get(route('vehicle-ref.makes.show', $this->toyota))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VehicleRef/Makes/Show')
                ->where('stats.models', 1)
                ->where('stats.engines', 1)
                ->where('stats.parts', 1)
                ->where('models.0.name', 'Hilux')
                ->where('models.0.parts_count', 1)
                ->where('engines.0.code', '1GD-FTV'));
    }

    public function test_make_index_carries_fitted_part_counts_and_status_filter(): void
    {
        VehicleMake::factory()->create(['name' => 'Datsun', 'code' => 'DAT', 'is_active' => false]);

        $this->get(route('vehicle-ref.makes.index', ['status' => 'active']))
            ->assertInertia(fn ($page) => $page
                ->has('makes.data', 1)
                ->where('makes.data.0.parts_count', 1));
    }

    public function test_used_make_is_deactivated_not_deleted_and_unused_make_is_deleted(): void
    {
        $this->delete(route('vehicle-ref.makes.destroy', $this->toyota))->assertSessionHas('success');
        $this->assertFalse($this->toyota->fresh()->is_active);
        $this->assertSame(1, PartFitment::count(), 'fitments must survive');

        $spare = VehicleMake::factory()->create(['name' => 'Proton', 'code' => 'PRO']);
        $this->delete(route('vehicle-ref.makes.destroy', $spare))->assertRedirect(route('vehicle-ref.makes.index'));
        $this->assertNull(VehicleMake::find($spare->id));
    }

    public function test_make_update_validates_unique_code(): void
    {
        VehicleMake::factory()->create(['name' => 'Nissan', 'code' => 'NIS']);

        $this->patch(route('vehicle-ref.makes.update', $this->toyota), ['name' => 'Toyota', 'code' => 'nis'])
            ->assertSessionHasErrors('code');

        $this->patch(route('vehicle-ref.makes.update', $this->toyota), ['name' => 'Toyota SA', 'code' => 'toy', 'sort_order' => 1, 'is_active' => true])
            ->assertSessionHasNoErrors();
        $this->assertSame('Toyota SA', $this->toyota->fresh()->name);
    }

    public function test_engine_detail_lists_vehicles_and_parts(): void
    {
        $this->get(route('vehicle-ref.engines.show', $this->engine))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('VehicleRef/Engines/Show')
                ->where('variants.0.name', '2.8 GD-6')
                ->where('parts.0.part_number', '04152-38020')
                ->where('partsTotal', 1));
    }

    public function test_engine_rename_carries_variants_and_fitments(): void
    {
        $this->patch(route('vehicle-ref.engines.update', $this->engine), ['code' => '1GD-FTV-E', 'make_id' => $this->toyota->id])
            ->assertRedirect(route('vehicle-ref.engines.show', $this->engine));

        $this->assertSame('1GD-FTV-E', $this->gd6->fresh()->engine_code);
        $this->assertSame('1GD-FTV-E', PartFitment::first()->engine_code);
    }

    public function test_engine_in_use_cannot_be_deleted_but_unused_one_can(): void
    {
        $this->delete(route('vehicle-ref.engines.destroy', $this->engine))->assertSessionHas('error');
        $this->assertNotNull($this->engine->fresh());

        $spare = EngineCode::create(['code' => 'ZZ-1']);
        $this->delete(route('vehicle-ref.engines.destroy', $spare))->assertSessionHas('success');
        $this->assertNull(EngineCode::find($spare->id));
    }

    public function test_fitment_rows_carry_till_fields_and_vehicle_label(): void
    {
        $this->get(route('vehicle-ref.fitment', ['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('vehicleLabel', 'Toyota Hilux')
                ->where('results.0.part_number', '04152-38020')
                ->has('results.0.priced')
                ->has('results.0.available')
                ->has('results.0.bin')
                ->where('results.0.fits.0.engine_code', '1GD-FTV'));
    }

    public function test_type_a_vehicle_search_finds_variant_model_and_year(): void
    {
        $hits = $this->getJson(route('vehicle-ref.fitment.vehicles', ['q' => 'hilux gd-6 2018']))->assertOk()->json('vehicles');

        $this->assertSame($this->gd6->id, $hits[0]['variant_id']);
        $this->assertSame('2018', $hits[0]['year']);
        $this->assertContains($this->hilux->id, array_column($hits, 'model_id'));
        $this->assertSame([], $this->getJson(route('vehicle-ref.fitment.vehicles', ['q' => 'x']))->json('vehicles'));
    }

    public function test_model_edit_and_variant_edit_and_delete(): void
    {
        $this->patch(route('vehicle-ref.models.update', $this->hilux), ['make_id' => $this->toyota->id, 'name' => 'Hilux Legend', 'year_from' => 2016])
            ->assertSessionHasNoErrors();
        $this->assertSame('Hilux Legend', $this->hilux->fresh()->name);

        $this->patch(route('vehicle-ref.models.variants.update', [$this->hilux, $this->gd6]), ['name' => '2.8 GD-6 Raider', 'engine_code' => '1GD-FTV'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2.8 GD-6 Raider', $this->gd6->fresh()->name);

        $unused = VehicleVariant::factory()->create(['model_id' => $this->hilux->id, 'name' => '2.4 GD-6']);
        $this->delete(route('vehicle-ref.models.variants.destroy', [$this->hilux, $unused]))->assertSessionHas('success');
        $this->assertNull(VehicleVariant::find($unused->id));
    }

    public function test_model_with_fitments_is_retired_and_bare_model_is_deleted_with_its_variants(): void
    {
        $this->delete(route('vehicle-ref.models.destroy', $this->hilux))->assertSessionHas('success');
        $this->assertFalse($this->hilux->fresh()->is_active);

        $bare = VehicleModel::factory()->create(['make_id' => $this->toyota->id, 'name' => 'Etios']);
        $v = VehicleVariant::factory()->create(['model_id' => $bare->id, 'name' => '1.5']);
        $this->delete(route('vehicle-ref.models.destroy', $bare))->assertRedirect(route('vehicle-ref.models.index'));
        $this->assertNull(VehicleModel::find($bare->id));
        $this->assertNull(VehicleVariant::find($v->id));
    }
}
