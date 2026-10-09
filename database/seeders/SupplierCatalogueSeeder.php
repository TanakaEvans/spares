<?php

namespace Database\Seeders;

use App\Models\Part;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives every part a sourcing story: an approved supplier (one preferred) and a
 * cost on that supplier's active price list, so the reorder report can draft
 * POs and purchasing has costs to work from. Idempotent — only fills gaps.
 */
class SupplierCatalogueSeeder extends Seeder
{
    private const DEMO_SUPPLIERS = [
        ['number' => 'SUPP-00001', 'name' => 'Demo Filters Distributor', 'type' => 'local', 'city' => 'Polokwane', 'lead' => 3],
        ['number' => 'SUPP-00002', 'name' => 'Demo Auto Parts Wholesale', 'type' => 'local', 'city' => 'Johannesburg', 'lead' => 5],
        ['number' => 'SUPP-00003', 'name' => 'Demo Motor Spares (Limpopo)', 'type' => 'local', 'city' => 'Polokwane', 'lead' => 2],
        ['number' => 'SUPP-00004', 'name' => 'Demo Autoparts Export', 'type' => 'import', 'city' => 'Shanghai', 'lead' => 45],
        ['number' => 'SUPP-00005', 'name' => 'Demo Bearing & Seal Centre', 'type' => 'local', 'city' => 'Pretoria', 'lead' => 1],
    ];

    public function run(): void
    {
        // Make sure a usable supplier base exists.
        foreach (self::DEMO_SUPPLIERS as $s) {
            Supplier::updateOrCreate(['supplier_number' => $s['number']], [
                'name' => $s['name'], 'type' => $s['type'], 'city' => $s['city'],
                'lead_time_days' => $s['lead'], 'payment_terms_days' => 30, 'is_active' => true,
            ]);
        }
        $supplierIds = Supplier::where('is_active', true)->pluck('id')->all();
        if ($supplierIds === []) {
            return;
        }

        // Parts that still have no preferred supplier.
        $preferred = DB::table('approved_suppliers')->where('is_preferred', true)->pluck('part_id')->flip();
        $parts = Part::query()->select('id', 'part_number', 'description', 'brand_id')->get();
        $now = now();

        $approvedRows = [];
        $assignments = []; // part_id => supplier_id (for price lists)
        foreach ($parts as $part) {
            if (isset($preferred[$part->id])) {
                continue;
            }
            $sid = $supplierIds[$part->id % count($supplierIds)]; // deterministic spread
            $assignments[$part->id] = $sid;
            $approvedRows[] = ['part_id' => $part->id, 'supplier_id' => $sid, 'is_preferred' => true,
                'lead_time_days' => null, 'created_at' => $now, 'updated_at' => $now];
        }

        // Insert approved rows; existing (part,supplier) pairs just get promoted to preferred.
        foreach (array_chunk($approvedRows, 1000) as $chunk) {
            DB::table('approved_suppliers')->upsert($chunk, ['part_id', 'supplier_id'], ['is_preferred', 'updated_at']);
        }

        // One active price list per supplier, with a cost for each assigned part (~55% of retail).
        $retail = DB::table('price_list_items as pi')
            ->join('price_lists as pl', fn ($j) => $j->on('pl.id', '=', 'pi.price_list_id')->where('pl.is_default', true))
            ->pluck('pi.price', 'pi.part_id');

        $bySupplier = [];
        foreach ($assignments as $partId => $sid) {
            $bySupplier[$sid][] = $partId;
        }

        $items = 0;
        foreach ($bySupplier as $sid => $partIds) {
            $list = SupplierPriceList::firstOrCreate(
                ['supplier_id' => $sid, 'status' => 'active'],
                ['name' => 'Catalogue '.now()->format('Y'), 'effective_date' => now()->toDateString(), 'matched_count' => 0, 'unmatched_count' => 0]
            );
            $existing = DB::table('supplier_price_list_items')->where('price_list_id', $list->id)->pluck('part_id')->flip();
            $rows = [];
            foreach ($partIds as $pid) {
                if (isset($existing[$pid])) {
                    continue;
                }
                $price = (float) ($retail[$pid] ?? 10);
                $part = $parts->firstWhere('id', $pid);
                $rows[] = ['price_list_id' => $list->id, 'part_id' => $pid, 'supplier_part_number' => $part?->part_number,
                    'description' => $part?->description, 'cost_price' => round(max(0.5, $price * 0.55), 2),
                    'minimum_qty' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('supplier_price_list_items')->insert($chunk);
            }
            $items += count($rows);
            $list->update(['matched_count' => DB::table('supplier_price_list_items')->where('price_list_id', $list->id)->count()]);
        }

        $this->command?->info(sprintf('Supplier catalogue: %d parts given a preferred supplier, %d cost lines added.', count($assignments), $items));
    }
}
