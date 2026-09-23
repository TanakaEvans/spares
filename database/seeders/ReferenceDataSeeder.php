<?php

namespace Database\Seeders;

use App\Models\EngineCode;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\UnitOfMeasure;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Database\Seeder;

/**
 * Loads the bundled CSV seed packs (database/data/) — the local-first data
 * strategy (docs/integrations/local-data-seeding.md). Idempotent: safe to
 * re-run after editing a CSV.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnits();
        $this->seedPartBrands();
        $this->seedPartCategories();
        $this->seedVehicleMakes();
        $this->seedVehicleModels();
        $this->seedEngineCodes();
        $this->seedVehicleVariants();

        $this->command?->info('Reference data seeded from database/data/ packs.');
    }

    private function csv(string $file): \Generator
    {
        $handle = fopen(database_path("data/{$file}"), 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                yield array_combine($header, array_map(
                    fn ($v) => $v === '' ? null : trim($v),
                    $row
                ));
            }
        }

        fclose($handle);
    }

    private function seedUnits(): void
    {
        foreach ($this->csv('units_of_measure.csv') as $row) {
            UnitOfMeasure::updateOrCreate(['abbreviation' => $row['abbreviation']], $row);
        }
    }

    private function seedPartBrands(): void
    {
        foreach ($this->csv('part_brands.csv') as $row) {
            $row['is_oem_brand'] = (bool) $row['is_oem_brand'];
            PartBrand::updateOrCreate(['code' => $row['code']], $row);
        }
    }

    private function seedPartCategories(): void
    {
        // Two passes: create all, then wire parents (order-independent CSV).
        $rows = iterator_to_array($this->csv('part_categories.csv'));

        foreach ($rows as $row) {
            PartCategory::updateOrCreate(['code' => $row['code']], [
                'name' => $row['name'],
                'sort_order' => (int) ($row['sort_order'] ?? 100),
            ]);
        }

        $byCode = PartCategory::pluck('id', 'code');

        foreach ($rows as $row) {
            if ($row['parent_code']) {
                PartCategory::where('code', $row['code'])
                    ->update(['parent_id' => $byCode[$row['parent_code']] ?? null]);
            }
        }
    }

    private function seedVehicleMakes(): void
    {
        foreach ($this->csv('vehicle_makes.csv') as $row) {
            VehicleMake::updateOrCreate(['code' => $row['code']], $row);
        }
    }

    private function seedVehicleModels(): void
    {
        $makes = VehicleMake::pluck('id', 'code');

        foreach ($this->csv('vehicle_models.csv') as $row) {
            $makeId = $makes[$row['make_code']] ?? null;
            if ($makeId === null) {
                continue;
            }

            VehicleModel::updateOrCreate(
                ['make_id' => $makeId, 'name' => $row['name'], 'generation' => null],
                [
                    'body_type' => $row['body_type'],
                    'year_from' => $row['year_from'],
                    'year_to' => $row['year_to'],
                ]
            );
        }
    }

    private function seedEngineCodes(): void
    {
        $makes = VehicleMake::pluck('id', 'code');

        foreach ($this->csv('engine_codes.csv') as $row) {
            EngineCode::updateOrCreate(['code' => $row['code']], [
                'make_id' => $makes[$row['make_code']] ?? null,
                'description' => $row['description'],
                'capacity_cc' => $row['capacity_cc'],
                'fuel_type' => $row['fuel_type'],
                'aspiration' => $row['aspiration'],
                'cylinders' => $row['cylinders'],
            ]);
        }
    }

    private function seedVehicleVariants(): void
    {
        $makes = VehicleMake::pluck('id', 'code');
        $models = VehicleModel::get(['id', 'make_id', 'name'])
            ->keyBy(fn ($m) => $m->make_id.'|'.$m->name);

        foreach ($this->csv('vehicle_variants.csv') as $row) {
            $makeId = $makes[$row['make_code']] ?? null;
            $model = $makeId ? $models[$makeId.'|'.$row['model_name']] ?? null : null;
            if ($model === null) {
                continue;
            }

            VehicleVariant::updateOrCreate(
                ['model_id' => $model->id, 'name' => $row['name']],
                [
                    'engine_code' => $row['engine_code'],
                    'engine_size_cc' => $row['engine_size_cc'],
                    'fuel_type' => $row['fuel_type'],
                    'power_kw' => $row['power_kw'],
                    'transmission' => $row['transmission'],
                    'drive' => $row['drive'],
                    'year_from' => $row['year_from'],
                    'year_to' => $row['year_to'],
                ]
            );
        }
    }
}
