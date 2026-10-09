<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse layout: a bin grid per branch (aisle-rack-shelf) and a home bin for
 * every stock level that has none, so pick lists and stock takes have locations.
 * Idempotent — never moves stock that already has a bin.
 */
class StockBinSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        foreach (Branch::all() as $branch) {
            // A-C aisles × 1-4 racks × 1-3 shelves = 36 bins per branch.
            $rows = [];
            foreach (['A', 'B', 'C'] as $aisle) {
                for ($rack = 1; $rack <= 4; $rack++) {
                    for ($shelf = 1; $shelf <= 3; $shelf++) {
                        $rows[] = ['branch_id' => $branch->id, 'code' => sprintf('%s-%02d-%02d', $aisle, $rack, $shelf),
                            'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
            }
            DB::table('bin_locations')->upsert($rows, ['branch_id', 'code'], ['is_active', 'updated_at']);

            $bins = DB::table('bin_locations')->where('branch_id', $branch->id)->where('is_active', true)->orderBy('code')->pluck('id')->all();
            if ($bins === []) {
                continue;
            }

            // Home every un-binned stock level in this branch, spread across the grid.
            $levels = DB::table('stock_levels')->where('branch_id', $branch->id)->whereNull('bin_location_id')->orderBy('part_id')->pluck('id', 'part_id');
            $i = 0;
            foreach ($levels as $partId => $levelId) {
                DB::table('stock_levels')->where('id', $levelId)->update(['bin_location_id' => $bins[$i++ % count($bins)]]);
            }
            $this->command?->info("Bins: {$branch->name} — ".count($bins).' bins, '.$levels->count().' stock levels homed.');
        }
    }
}
