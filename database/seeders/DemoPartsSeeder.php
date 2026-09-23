<?php

namespace Database\Seeders;

use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\PartCrossReference;
use App\Models\PartFitment;
use App\Models\PartSupersession;
use App\Models\StockLedgerEntry;
use App\Models\UnitOfMeasure;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use App\Services\StockLedgerService;
use Illuminate\Database\Seeder;

/**
 * Demo/dev world: a coherent small catalogue with fitments, cross-refs,
 * a supersession chain, bins and opening stock. NEVER runs in production
 * (testing-strategy.md §5). Part numbers are illustrative demo data.
 */
class DemoPartsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoPartsSeeder is blocked in production.');

            return;
        }

        $branch = Branch::where('is_main_branch', true)->first() ?? Branch::first();
        if (! $branch) {
            $this->command?->error('Seed a branch first (SuperuserSeeder).');

            return;
        }

        $cat = fn (string $code) => PartCategory::where('code', $code)->firstOrFail()->id;
        $brand = fn (string $code) => PartBrand::where('code', $code)->firstOrFail()->id;
        $ea = UnitOfMeasure::where('abbreviation', 'ea')->firstOrFail()->id;
        $litre = UnitOfMeasure::where('abbreviation', 'L')->firstOrFail()->id;

        $toy = VehicleMake::where('code', 'TOY')->firstOrFail();
        $vw = VehicleMake::where('code', 'VW')->firstOrFail();
        $nis = VehicleMake::where('code', 'NIS')->firstOrFail();
        $hilux = VehicleModel::where('make_id', $toy->id)->where('name', 'Hilux')->firstOrFail();
        $fortuner = VehicleModel::where('make_id', $toy->id)->where('name', 'Fortuner')->firstOrFail();
        $poloVivo = VehicleModel::where('make_id', $vw->id)->where('name', 'Polo Vivo')->firstOrFail();
        $np200 = VehicleModel::where('make_id', $nis->id)->where('name', 'NP200')->firstOrFail();
        $gd6 = VehicleVariant::where('model_id', $hilux->id)->where('name', 'like', '2.8 GD-6%')->first();

        // Bins
        $bins = [];
        foreach (['A-01-01', 'A-01-02', 'A-02-01', 'B-01-01', 'B-02-01', 'OIL-01'] as $code) {
            $bins[$code] = BinLocation::updateOrCreate(
                ['branch_id' => $branch->id, 'code' => $code],
                ['is_active' => true]
            );
        }

        $parts = [
            [
                'part_number' => '04152-38020', 'oem_number' => '04152-38020',
                'description' => 'Oil Filter Element — Toyota Hilux/Fortuner 2.8 & 2.4 GD-6', 'short_description' => 'Oil filter GD-6',
                'category' => 'ENG-FLT-OIL', 'brand' => 'TOYG', 'unit' => $ea, 'is_oem' => true,
                'barcode_ean' => '4901234567891', 'bin' => 'A-01-01', 'opening_qty' => 24, 'cost' => 5.80,
                'crossRefs' => [
                    ['Z762', 'GUD', 'aftermarket'], ['CH11934', 'FRAM', 'aftermarket'], ['HU 7019 z', 'MANN', 'aftermarket'],
                ],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'variant' => '2.8 GD-6', 'year_from' => 2016, 'engine' => '1GD-FTV'],
                    ['make' => 'TOY', 'model' => 'Hilux', 'engine' => '2GD-FTV', 'year_from' => 2016],
                    ['make' => 'TOY', 'model' => 'Fortuner', 'year_from' => 2016, 'engine' => '1GD-FTV'],
                ],
            ],
            [
                'part_number' => 'Z762', 'oem_number' => '04152-38020',
                'description' => 'Oil Filter — GUD equivalent for Toyota GD-6', 'short_description' => 'Oil filter GD-6 (GUD)',
                'category' => 'ENG-FLT-OIL', 'brand' => 'GUD', 'unit' => $ea, 'is_oem' => false,
                'barcode_ean' => '6001234500017', 'bin' => 'A-01-01', 'opening_qty' => 36, 'cost' => 3.20,
                'crossRefs' => [['04152-38020', 'TOYG', 'oem']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'year_from' => 2016, 'engine' => '1GD-FTV'],
                    ['make' => 'TOY', 'model' => 'Fortuner', 'year_from' => 2016],
                ],
            ],
            [
                'part_number' => '17801-0L040', 'oem_number' => '17801-0L040',
                'description' => 'Air Filter — Toyota Hilux/Fortuner GD-6', 'short_description' => 'Air filter GD-6',
                'category' => 'ENG-FLT-AIR', 'brand' => 'TOYG', 'unit' => $ea, 'is_oem' => true,
                'bin' => 'A-01-02', 'opening_qty' => 18, 'cost' => 9.40,
                'crossRefs' => [['AG1494', 'GUD', 'aftermarket'], ['CA11946', 'FRAM', 'aftermarket']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'year_from' => 2016],
                    ['make' => 'TOY', 'model' => 'Fortuner', 'year_from' => 2016],
                ],
            ],
            [
                'part_number' => '23390-0L070', 'oem_number' => '23390-0L070',
                'description' => 'Fuel Filter — Toyota Hilux/Fortuner GD-6 diesel', 'short_description' => 'Fuel filter GD-6',
                'category' => 'ENG-FLT-FUE', 'brand' => 'TOYG', 'unit' => $ea, 'is_oem' => true,
                'bin' => 'A-01-02', 'opening_qty' => 15, 'cost' => 14.20,
                'crossRefs' => [['Z1042', 'GUD', 'aftermarket']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'year_from' => 2016],
                    ['make' => 'TOY', 'model' => 'Fortuner', 'year_from' => 2016],
                ],
            ],
            [
                'part_number' => 'DB2074', 'oem_number' => '04465-0K420',
                'description' => 'Front Brake Pads — Toyota Hilux 2016+ / Fortuner', 'short_description' => 'Brake pads front Hilux',
                'category' => 'BRK-PAD', 'brand' => 'BENDX', 'unit' => $ea, 'is_oem' => false,
                'bin' => 'B-01-01', 'opening_qty' => 12, 'cost' => 22.50,
                'crossRefs' => [['04465-0K420', 'TOYG', 'oem'], ['FDB4898', 'FERO', 'aftermarket']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'year_from' => 2016],
                    ['make' => 'TOY', 'model' => 'Fortuner', 'year_from' => 2016],
                ],
            ],
            [
                'part_number' => 'BKR6E-11', 'oem_number' => '90919-01192',
                'description' => 'Spark Plug — NGK BKR6E-11 (Toyota petrol, many)', 'short_description' => 'NGK plug BKR6E-11',
                'category' => 'ELE-PLG', 'brand' => 'NGK', 'unit' => $ea, 'is_oem' => false,
                'barcode_ean' => '0087295131343', 'bin' => 'B-02-01', 'opening_qty' => 60, 'cost' => 2.10,
                'crossRefs' => [['90919-01192', 'TOYG', 'oem'], ['K20PR-U11', 'DENSO', 'aftermarket']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'engine' => '2TR-FE'],
                    ['make' => 'TOY', 'model' => 'Corolla'],
                    ['make' => 'TOY', 'model' => 'Vitz'],
                ],
            ],
            [
                'part_number' => 'MAG-5W40-5L',
                'description' => 'Castrol Magnatec 5W-40 Engine Oil 5L', 'short_description' => 'Magnatec 5W-40 5L',
                'category' => 'OIL-ENG', 'brand' => 'CAST', 'unit' => $litre, 'is_oem' => false,
                'barcode_ean' => '4008177077705', 'bin' => 'OIL-01', 'opening_qty' => 40, 'cost' => 21.00,
                'crossRefs' => [],
                'fitments' => [],
            ],
            [
                'part_number' => '03C115561H', 'oem_number' => '03C115561H',
                'description' => 'Oil Filter — VW Polo Vivo/Polo 1.4/1.6', 'short_description' => 'Oil filter Polo',
                'category' => 'ENG-FLT-OIL', 'brand' => 'VWG', 'unit' => $ea, 'is_oem' => true,
                'bin' => 'A-02-01', 'opening_qty' => 20, 'cost' => 4.60,
                'crossRefs' => [['Z179', 'GUD', 'aftermarket'], ['W 712/94', 'MANN', 'aftermarket']],
                'fitments' => [
                    ['make' => 'VW', 'model' => 'Polo Vivo'],
                    ['make' => 'VW', 'model' => 'Polo'],
                ],
            ],
            [
                'part_number' => 'Z131', 'oem_number' => '15208-00Q0A',
                'description' => 'Oil Filter — Nissan NP200 1.6 / Renault', 'short_description' => 'Oil filter NP200',
                'category' => 'ENG-FLT-OIL', 'brand' => 'GUD', 'unit' => $ea, 'is_oem' => false,
                'bin' => 'A-02-01', 'opening_qty' => 16, 'cost' => 3.00,
                'crossRefs' => [['15208-00Q0A', 'NISG', 'oem']],
                'fitments' => [
                    ['make' => 'NIS', 'model' => 'NP200'],
                ],
            ],
            [
                'part_number' => 'G-HILUX-F', 'oem_number' => '48510-09J51',
                'description' => 'Shock Absorber Front — Gabriel, Toyota Hilux 2005-2016', 'short_description' => 'Shock front Hilux 05-16',
                'category' => 'SUS-SHK', 'brand' => 'GABR', 'unit' => $ea, 'is_oem' => false,
                'bin' => 'B-01-01', 'opening_qty' => 8, 'cost' => 28.00,
                'crossRefs' => [['48510-09J51', 'TOYG', 'oem']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Hilux', 'year_from' => 2005, 'year_to' => 2016],
                ],
            ],
            // Supersession pair: old number replaced by new.
            [
                'part_number' => '90915-YZZD2', 'oem_number' => '90915-YZZD2',
                'description' => 'Oil Filter — Toyota (superseded number)', 'short_description' => 'Oil filter (old no.)',
                'category' => 'ENG-FLT-OIL', 'brand' => 'TOYG', 'unit' => $ea, 'is_oem' => true,
                'is_discontinued' => true, 'is_active' => false,
                'opening_qty' => 0, 'cost' => 0,
                'crossRefs' => [], 'fitments' => [],
            ],
            [
                'part_number' => '90915-YZZD4', 'oem_number' => '90915-YZZD4',
                'description' => 'Oil Filter — Toyota petrol (Corolla/Vitz/Quantum)', 'short_description' => 'Oil filter Toyota petrol',
                'category' => 'ENG-FLT-OIL', 'brand' => 'TOYG', 'unit' => $ea, 'is_oem' => true,
                'bin' => 'A-01-01', 'opening_qty' => 30, 'cost' => 4.10,
                'crossRefs' => [['Z88', 'GUD', 'aftermarket'], ['PH4998', 'FRAM', 'aftermarket']],
                'fitments' => [
                    ['make' => 'TOY', 'model' => 'Corolla'],
                    ['make' => 'TOY', 'model' => 'Vitz'],
                    ['make' => 'TOY', 'model' => 'Quantum', 'engine' => '2TR-FE'],
                ],
            ],
        ];

        $stock = app(StockLedgerService::class);
        $makes = VehicleMake::pluck('id', 'code');

        foreach ($parts as $def) {
            $part = Part::updateOrCreate(['part_number' => $def['part_number']], [
                'oem_number' => $def['oem_number'] ?? null,
                'description' => $def['description'],
                'short_description' => $def['short_description'] ?? null,
                'category_id' => $cat($def['category']),
                'brand_id' => $brand($def['brand']),
                'unit_id' => $def['unit'],
                'barcode_ean' => $def['barcode_ean'] ?? null,
                'is_oem' => $def['is_oem'] ?? false,
                'is_active' => $def['is_active'] ?? true,
                'is_discontinued' => $def['is_discontinued'] ?? false,
            ]);

            foreach ($def['crossRefs'] as [$number, $brandCode, $type]) {
                PartCrossReference::updateOrCreate(
                    ['part_id' => $part->id, 'reference_number' => $number],
                    ['brand_id' => $brand($brandCode), 'type' => $type]
                );
            }

            foreach ($def['fitments'] as $fit) {
                $makeId = $makes[$fit['make']];
                $model = isset($fit['model'])
                    ? VehicleModel::where('make_id', $makeId)->where('name', $fit['model'])->first()
                    : null;
                $variant = isset($fit['variant']) && $model
                    ? VehicleVariant::where('model_id', $model->id)->where('name', 'like', $fit['variant'].'%')->first()
                    : null;

                PartFitment::updateOrCreate([
                    'part_id' => $part->id,
                    'make_id' => $makeId,
                    'model_id' => $model?->id,
                    'variant_id' => $variant?->id,
                    'engine_code' => $fit['engine'] ?? null,
                ], [
                    'year_from' => $fit['year_from'] ?? null,
                    'year_to' => $fit['year_to'] ?? null,
                    'source' => 'manual',
                    'confirmed' => true,
                ]);
            }

            // Opening stock through the ledger — never raw writes.
            if (($def['opening_qty'] ?? 0) > 0) {
                $alreadyOpened = StockLedgerEntry::where('part_id', $part->id)
                    ->where('branch_id', $branch->id)
                    ->where('transaction_type', 'OPENING_BALANCE')
                    ->exists();

                if (! $alreadyOpened) {
                    $stock->post(
                        $part->id, $branch, 'OPENING_BALANCE',
                        $def['opening_qty'], $def['cost'],
                        'DemoSeeder', $part->id, 'Demo opening stock'
                    );

                    if (isset($def['bin'])) {
                        $part->stockLevels()
                            ->where('branch_id', $branch->id)
                            ->update(['bin_location_id' => $bins[$def['bin']]->id]);
                    }
                }
            }
        }

        // Wire the supersession chain: YZZD2 → YZZD4.
        $old = Part::where('part_number', '90915-YZZD2')->first();
        $new = Part::where('part_number', '90915-YZZD4')->first();
        if ($old && $new) {
            PartSupersession::updateOrCreate(
                ['old_part_id' => $old->id, 'new_part_id' => $new->id],
                ['reason' => 'Toyota part number change', 'is_active' => true]
            );
        }

        $this->command?->info('Demo parts, fitments, cross-refs, bins and opening stock seeded.');
    }
}
