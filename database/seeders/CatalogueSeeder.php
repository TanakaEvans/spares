<?php

namespace Database\Seeders;

use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\PriceList;
use App\Models\UnitOfMeasure;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Large local parts catalogue — the "brand fitment guide" inflow from
 * docs/integrations/local-data-seeding.md (②): OEM ↔ aftermarket equivalents,
 * cross-referenced and fitted to the regional car parc. NEVER runs in
 * production. Idempotent via a count guard (numbering is sequence-based).
 *
 * Fast bulk inserts; part ids come from the contiguous auto-increment of a
 * single multi-row INSERT (MySQL/InnoDB), so cross-refs/fitments/prices can be
 * written without re-querying.
 */
class CatalogueSeeder extends Seeder
{
    private int $seq = 500000;

    /** [category code, label, fuel any|petrol|diesel, [aftermarket brand codes], [minCost, maxCost]] */
    private array $types = [
        ['ENG-FLT-OIL', 'Oil Filter', 'any', ['GUD', 'FRAM', 'MANN', 'BOSCH'], [3, 9]],
        ['ENG-FLT-AIR', 'Air Filter', 'any', ['GUD', 'FRAM', 'MANN'], [6, 18]],
        ['ENG-FLT-FUE', 'Fuel Filter', 'any', ['GUD', 'FRAM', 'BOSCH'], [8, 24]],
        ['ENG-FLT-CAB', 'Cabin Filter', 'any', ['GUD', 'FRAM'], [5, 15]],
        ['ELE-PLG', 'Spark Plug', 'petrol', ['NGK', 'DENSO', 'BOSCH', 'CHAMP'], [2, 7]],
        ['ELE-PLG', 'Glow Plug', 'diesel', ['NGK', 'BOSCH', 'DENSO'], [4, 12]],
        ['BRK-PAD', 'Front Brake Pad Set', 'any', ['BENDX', 'FERO', 'ATE', 'TRW'], [15, 48]],
        ['BRK-PAD', 'Rear Brake Pad Set', 'any', ['BENDX', 'FERO', 'TRW'], [14, 42]],
        ['BRK-DSC', 'Front Brake Disc', 'any', ['BENDX', 'ATE', 'TRW'], [20, 65]],
        ['BRK-DSC', 'Rear Brake Disc', 'any', ['BENDX', 'ATE'], [18, 58]],
        ['BRK-SHO', 'Rear Brake Shoe Set', 'any', ['BENDX', 'FERO'], [12, 32]],
        ['ENG-CLG-WPP', 'Water Pump', 'any', ['GMB', 'GATES', 'GUD'], [18, 60]],
        ['ENG-CLG-THS', 'Thermostat', 'any', ['GATES', 'MANN'], [6, 20]],
        ['ENG-CLG-RAD', 'Radiator', 'any', ['GUD', 'GATES'], [40, 150]],
        ['BLT-TIM', 'Timing Belt', 'any', ['GATES', 'DAYCO'], [12, 48]],
        ['ENG-TIM', 'Timing Belt Kit', 'any', ['GATES', 'DAYCO'], [30, 130]],
        ['BLT-FAN', 'Drive / Serpentine Belt', 'any', ['GATES', 'DAYCO'], [6, 28]],
        ['SUS-SHK', 'Front Shock Absorber', 'any', ['GABR', 'MONRO', 'KYB'], [22, 75]],
        ['SUS-SHK', 'Rear Shock Absorber', 'any', ['GABR', 'MONRO', 'KYB'], [20, 68]],
        ['SUS-TRE', 'Tie Rod End', 'any', ['GMB', 'TRW'], [8, 26]],
        ['SUS-BJT', 'Ball Joint', 'any', ['GMB', 'TRW'], [8, 28]],
        ['SUS-WBR', 'Front Wheel Bearing', 'any', ['KOYO', 'SKF', 'NSK'], [12, 42]],
        ['SUS-CVJ', 'CV Joint', 'any', ['GMB', 'GUD'], [25, 80]],
        ['SUS-BSH', 'Control Arm Bush', 'any', ['GMB'], [4, 15]],
        ['TRN-CLU', 'Clutch Kit', 'any', ['EXEDY', 'LUK'], [60, 240]],
        ['ENG-MNT', 'Engine Mounting', 'any', ['GMB'], [10, 38]],
        ['BOD-WIP', 'Wiper Blade', 'any', ['BOSCH', 'DOE'], [4, 14]],
        ['ELE-ALT', 'Alternator', 'any', ['BOSCH', 'DENSO'], [80, 280]],
    ];

    private array $brandPrefix = [
        'GUD' => 'Z', 'FRAM' => 'PH', 'MANN' => 'W', 'BOSCH' => 'F026', 'NGK' => 'BKR', 'DENSO' => 'DX',
        'CHAMP' => 'RN', 'BENDX' => 'DB', 'FERO' => 'FDB', 'ATE' => '13-', 'TRW' => 'GDB', 'GMB' => 'GMB-',
        'GATES' => 'T', 'DAYCO' => '94', 'GABR' => 'G', 'MONRO' => 'R', 'KYB' => '34', 'KOYO' => 'DAC',
        'SKF' => 'VKBA', 'NSK' => '45BWD', 'EXEDY' => 'TYK-', 'LUK' => '62', 'DOE' => 'WB', 'WILL' => '', 'EXIDE' => '',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('CatalogueSeeder is blocked in production.');

            return;
        }
        $cats = PartCategory::pluck('id', 'code')->all();
        $brands = PartBrand::pluck('id', 'code')->all();
        $ea = UnitOfMeasure::where('abbreviation', 'ea')->value('id');
        $litre = UnitOfMeasure::where('abbreviation', 'L')->value('id') ?? $ea;
        $retail = PriceList::where('is_default', true)->value('id')
            ?? PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true])->id;

        // Genuine (OEM) brand per make.
        $genuine = [];
        $models = VehicleModel::with('make:id,code,name')->where('is_active', true)->get();
        foreach ($models->pluck('make')->unique('id') as $make) {
            $code = strtoupper(substr($make->code, 0, 4)).'G';
            $genuine[$make->id] = PartBrand::firstOrCreate(
                ['code' => $code],
                ['name' => $make->name.' Genuine', 'is_oem_brand' => true]
            )->id;
        }

        $dieselHints = ['hilux', 'ranger', 'navara', 'np300', 'd-max', 'kb', 'fortuner', 'everest', 'pajero',
            'triton', 'land cruiser', 'prado', 'hardbody', 'amarok', 'mu-x', 'hiace', 'quantum', 'h-100', 'bakkie', 'canter', 'sprinter'];

        $parts = [];   // pending part rows
        $meta = [];    // parallel meta: ['fitment'=>..., 'xrefs'=>[[num,brandId,type]], 'price'=>float]
        $counters = ['parts' => 0, 'xrefs' => 0, 'fitments' => 0, 'prices' => 0];

        $flush = function () use (&$parts, &$meta, &$counters, $retail) {
            if ($parts === []) {
                return;
            }
            DB::table('parts')->insert($parts);
            $firstId = (int) DB::getPdo()->lastInsertId();

            $xrefRows = [];
            $fitRows = [];
            $priceRows = [];
            foreach ($parts as $i => $_) {
                $pid = $firstId + $i;
                $m = $meta[$i];
                foreach ($m['xrefs'] as [$num, $bid, $type]) {
                    $xrefRows[] = ['part_id' => $pid, 'reference_number' => $num, 'brand_id' => $bid, 'type' => $type, 'created_at' => now(), 'updated_at' => now()];
                }
                $f = $m['fitment'];
                $fitRows[] = ['part_id' => $pid, 'make_id' => $f['make_id'], 'model_id' => $f['model_id'], 'variant_id' => null,
                    'year_from' => $f['year_from'], 'year_to' => $f['year_to'], 'engine_code' => null,
                    'source' => 'brand_guide', 'confirmed' => $f['confirmed'], 'created_at' => now(), 'updated_at' => now()];
                $priceRows[] = ['price_list_id' => $retail, 'part_id' => $pid, 'price' => $m['price'], 'created_at' => now(), 'updated_at' => now()];
            }
            foreach (array_chunk($xrefRows, 1000) as $c) {
                DB::table('part_cross_references')->insert($c);
            }
            foreach (array_chunk($fitRows, 1000) as $c) {
                DB::table('part_fitments')->insert($c);
            }
            foreach (array_chunk($priceRows, 1000) as $c) {
                DB::table('price_list_items')->insert($c);
            }
            $counters['parts'] += count($parts);
            $counters['xrefs'] += count($xrefRows);
            $counters['fitments'] += count($fitRows);
            $counters['prices'] += count($priceRows);
            $parts = [];
            $meta = [];
        };

        // The heavy model-derived catalogue seeds once; the universal
        // consumables below are idempotent and always refresh.
        $seedMain = \App\Models\Part::count() <= 5000;

        foreach ($seedMain ? $models : [] as $model) {
            $make = $model->make;
            $isDiesel = false;
            $hay = strtolower($model->name.' '.($model->body_type ?? ''));
            foreach ($dieselHints as $d) {
                if (str_contains($hay, $d)) {
                    $isDiesel = true;
                    break;
                }
            }
            $yearStr = $model->year_from.'–'.($model->year_to ?: 'on');

            foreach ($this->types as [$catCode, $label, $fuel, $aftBrands, $costRange]) {
                if ($fuel === 'petrol' && $isDiesel && ! str_contains($hay, 'hilux')) {
                    // diesel-only bakkies skip spark plugs (Hilux keeps both — petrol variants exist)
                    if (! in_array($model->name, ['Hilux', 'Ranger', 'Corolla'], true)) {
                        continue;
                    }
                }
                if ($fuel === 'diesel' && ! $isDiesel) {
                    continue;
                }

                $catId = $cats[$catCode] ?? null;
                if ($catId === null) {
                    continue;
                }

                $cost = mt_rand((int) ($costRange[0] * 100), (int) ($costRange[1] * 100)) / 100;
                $oemBrandId = $genuine[$make->id];
                $oemNumber = strtoupper($make->code).'-'.($this->seq++);

                // Build the group: OEM + one SKU per aftermarket brand.
                $group = [];
                $group[] = ['brandId' => $oemBrandId, 'number' => $oemNumber, 'is_oem' => true, 'brandCode' => $make->code];
                foreach ($aftBrands as $bc) {
                    if (! isset($brands[$bc])) {
                        continue;
                    }
                    $group[] = ['brandId' => $brands[$bc], 'number' => ($this->brandPrefix[$bc] ?? $bc).($this->seq++), 'is_oem' => false, 'brandCode' => $bc];
                }
                $numbers = array_column($group, 'number');
                $unitId = str_contains($catCode, 'OIL') ? $litre : $ea;

                foreach ($group as $sku) {
                    // Cross-references: OEM ↔ each other member.
                    $xrefs = [];
                    foreach ($group as $other) {
                        if ($other['number'] === $sku['number']) {
                            continue;
                        }
                        $xrefs[] = [$other['number'], $other['brandId'], $other['is_oem'] ? 'oem' : 'aftermarket'];
                    }

                    $parts[] = [
                        'part_number' => $sku['number'],
                        'oem_number' => $oemNumber,
                        'description' => "{$label} — {$make->name} {$model->name} ({$yearStr})",
                        'short_description' => "{$label} {$model->name}",
                        'category_id' => $catId,
                        'brand_id' => $sku['brandId'],
                        'unit_id' => $unitId,
                        'is_oem' => $sku['is_oem'],
                        'is_active' => true,
                        'is_discontinued' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $meta[] = [
                        'xrefs' => $xrefs,
                        'fitment' => ['make_id' => $make->id, 'model_id' => $model->id, 'year_from' => $model->year_from, 'year_to' => $model->year_to, 'confirmed' => $sku['is_oem'] ? 1 : 0],
                        'price' => round($cost * (str_contains($catCode, 'BRK') || str_contains($catCode, 'ALT') ? 1.8 : 2.0), 2),
                    ];

                    if (count($parts) >= 500) {
                        $flush();
                    }
                }
            }
        }

        $flush();

        $this->seedUniversal($cats, $brands, $ea, $litre, $retail, $counters);

        $this->command?->info(sprintf(
            'Catalogue seeded: %s parts, %s cross-refs, %s fitments, %s prices.',
            number_format($counters['parts']), number_format($counters['xrefs']),
            number_format($counters['fitments']), number_format($counters['prices'])
        ));
    }

    /** Vehicle-independent consumables: oils, batteries, bulbs, wipers. Idempotent. */
    private function seedUniversal(array $cats, array $brands, ?int $ea, ?int $litre, int $retail, array &$counters): void
    {
        $add = function (string $number, string $desc, ?int $catId, ?int $brandId, ?int $unit, float $price) use ($retail, &$counters) {
            $part = \App\Models\Part::updateOrCreate(['part_number' => $number], [
                'description' => $desc,
                'short_description' => \Illuminate\Support\Str::limit($desc, 40, ''),
                'category_id' => $catId, 'brand_id' => $brandId, 'unit_id' => $unit,
                'is_oem' => false, 'is_active' => true, 'is_discontinued' => false,
            ]);
            \App\Models\PriceListItem::updateOrCreate(
                ['price_list_id' => $retail, 'part_id' => $part->id], ['price' => $price]
            );
            $counters['parts']++;
            $counters['prices']++;
        };

        // Engine oils
        foreach (['5W-30', '5W-40', '10W-40', '15W-40', '20W-50', '0W-20'] as $grade) {
            foreach (['CAST' => 'Castrol', 'SHELL' => 'Shell Helix', 'TOTAL' => 'Total Quartz', 'ENGEN' => 'Engen'] as $bc => $bn) {
                foreach (['1L' => 6, '4L' => 20, '5L' => 24, '20L' => 90] as $size => $base) {
                    $add("OIL-{$bc}-".str_replace('W-', '', $grade)."-{$size}", "{$bn} {$grade} Engine Oil {$size}", $cats['OIL-ENG'] ?? null, $brands[$bc] ?? null, $litre, round($base * 1.6, 2));
                }
            }
        }
        // Gear/diff oils
        foreach (['75W-90', '80W-90', '85W-140'] as $grade) {
            foreach (['CAST' => 'Castrol', 'SHELL' => 'Shell Spirax', 'TOTAL' => 'Total'] as $bc => $bn) {
                foreach (['1L' => 8, '5L' => 30] as $size => $base) {
                    $add("GBX-{$bc}-".str_replace('W-', '', $grade)."-{$size}", "{$bn} {$grade} Gear Oil {$size}", $cats['OIL-GBX'] ?? null, $brands[$bc] ?? null, $litre, round($base * 1.6, 2));
                }
            }
        }
        // Batteries by group size
        foreach ([619, 628, 634, 636, 638, 646, 650, 652, 657, 658, 668, 674, 689] as $g) {
            foreach (['WILL' => 'Willard', 'EXIDE' => 'Exide'] as $bc => $bn) {
                $add("BAT-{$bc}-{$g}", "{$bn} Battery {$g} (12V)", $cats['ELE-BAT'] ?? null, $brands[$bc] ?? null, $ea, round(35 + ($g - 619) * 1.2, 2));
            }
        }
        // Bulbs
        foreach (['H1', 'H3', 'H4', 'H7', 'H11', 'HB3', 'HB4', 'P21W', 'W5W', 'T10'] as $b) {
            foreach (['BOSCH' => 'Bosch', 'DOE' => 'OES'] as $bc => $bn) {
                $add("BULB-{$bc}-{$b}", "{$bn} Bulb {$b} 12V", $cats['ELE-LMP'] ?? null, $brands[$bc] ?? null, $ea, round(mt_rand(150, 900) / 100, 2));
            }
        }
        // Wiper refills by length
        foreach ([350, 400, 450, 475, 500, 525, 550, 600, 650, 700] as $len) {
            foreach (['BOSCH' => 'Bosch', 'DOE' => 'OES'] as $bc => $bn) {
                $add("WIP-{$bc}-{$len}", "{$bn} Wiper Blade {$len}mm", $cats['BOD-WIP'] ?? null, $brands[$bc] ?? null, $ea, round(mt_rand(400, 1400) / 100, 2));
            }
        }
    }
}
