<?php

namespace Tests\Feature;

use App\Models\ApprovedSupplier;
use App\Models\Branch;
use App\Models\DataImport;
use App\Models\Part;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CsvImportService;
use App\Services\SupplierCatalogueImportService;
use Database\Seeders\NumberSequenceSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data Upload › Supplier Catalogue: a wholesaler's listing (codes + application
 * text, no prices) becomes searchable, correctly-categorised parts linked to
 * the supplier; next month's list updates in place and flags dropped lines.
 */
class SupplierCatalogueImportTest extends TestCase
{
    use RefreshDatabase;

    private const OCT_HEADER = ['Category', 'Category Desc', 'Item', 'Description1', 'Specification', 'UOM'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Branch::factory()->create(['is_main_branch' => true]);
        $role = Role::create(['name' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    private function upload(array $header, array $rows, array $extra = [])
    {
        $dir = storage_path('app/private/imports/data');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $out = fopen($dir.DIRECTORY_SEPARATOR.'catalogue-test.csv', 'w');
        foreach ([$header, ...$rows] as $line) {
            fputcsv($out, $line, ',', '"', '', "\n");
        }
        fclose($out);

        $csv = app(CsvImportService::class);
        $map = [];
        foreach (SupplierCatalogueImportService::FIELDS as $key => $def) {
            $map[$key] = $csv->guess($header, $def['keywords'], $def['label'], $key);
        }

        return $this->actingAs($this->user)->post(route('imports.store', 'catalogue'), [
            'path' => 'imports/data/catalogue-test.csv', 'original_name' => 'listing.xlsx', 'map' => $map,
        ] + $extra);
    }

    private function part(string $code): Part
    {
        return Part::with(['category', 'unit'])->where('part_number', $code)->firstOrFail();
    }

    public function test_listing_becomes_searchable_categorised_parts_linked_to_the_supplier(): void
    {
        (new ReferenceDataSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->upload(self::OCT_HEADER, [
            ['AF', 'AIR FILTER', 'AF213', 'SORENTO I 2.5CRDi 03-06', '', 'PC'],
            ['CO', 'CONTROL ARMS', 'CO225', 'HYU I10 09- LH', '', 'PC'],
            ['BL', 'BELT TENSIONER', 'BL170', 'ASTRA G/H/CORSA C V-BELT TENSIONER ASSY', '', 'PC'],
            ['MB', 'MAIN BEARINGS', 'MB28-1', 'OPEL 1.6/1.8/2.0 SE/SH/SV/XE 82-', '0', 'SET'],
            ['BD', 'BRAKE DISC', 'BD224', 'RUN-X 1.8 RSi 03- (FV)', 'D-275 H-49.2', 'PC'],
            ['AC', 'ACCESSORIES', 'AC13', 'TUBELESS TYRE VALVE (TR414) 10pcs/PACK', '', 'PAC'],
            ['AF', 'AIR FILTER', 'AF579', 'NEW ITEM.', '', 'PC'],
            ['ZZ', 'MYSTERY LINE', 'ZZ1', 'SOMETHING NEW', '', 'KIT'],
        ], ['supplier_name' => 'Mravat Wholesale'])->assertRedirect();

        $import = DataImport::latest('id')->first();
        $this->assertSame('catalogue', $import->entity);
        $this->assertSame(7, $import->created_count, json_encode($import->errors));
        $this->assertSame(1, $import->skipped_count);
        $this->assertStringContainsString('placeholder', $import->errors[0]['message']);

        // Application-only text gains its part type; self-describing text is left alone.
        $this->assertSame('AIR FILTER - SORENTO I 2.5CRDi 03-06', $this->part('AF213')->description);
        $this->assertSame('CONTROL ARM - HYU I10 09- LH', $this->part('CO225')->description);
        $this->assertSame('ASTRA G/H/CORSA C V-BELT TENSIONER ASSY', $this->part('BL170')->description);
        $this->assertSame('TUBELESS TYRE VALVE (TR414) 10pcs/PACK', $this->part('AC13')->description);
        // Size suffix spelled out; meaningful spec appended, "0" spec ignored.
        $this->assertSame('MAIN BEARINGS - OPEL 1.6/1.8/2.0 SE/SH/SV/XE 82- SIZE 0.25mm', $this->part('MB28-1')->description);
        $this->assertSame('BRAKE DISC - RUN-X 1.8 RSi 03- (FV) (D-275 H-49.2)', $this->part('BD224')->description);

        // Supplier categories land on our tree; supplier units on ours.
        $this->assertSame('ENG-FLT-AIR', $this->part('AF213')->category->code);
        $this->assertSame('SUS-BSH', $this->part('CO225')->category->code);
        $this->assertSame('ENG-BRG', $this->part('MB28-1')->category->code);
        $this->assertSame('MISC', $this->part('ZZ1')->category->code);
        $this->assertSame('ea', $this->part('AF213')->unit->abbreviation);
        $this->assertSame('set', $this->part('MB28-1')->unit->abbreviation);
        $this->assertSame('pk', $this->part('AC13')->unit->abbreviation);
        $this->assertSame('kit', $this->part('ZZ1')->unit->abbreviation);

        // The counter can find it by vehicle + type.
        $this->assertSame(['CO225'], Part::search('control arm i10')->pluck('part_number')->all());
        $this->assertSame(['AF213'], Part::search('sorento air filter')->pluck('part_number')->all());

        // Linked to the (new) supplier as preferred.
        $supplier = Supplier::where('name', 'Mravat Wholesale')->firstOrFail();
        $this->assertSame('wholesaler', $supplier->type);
        $this->assertSame(7, ApprovedSupplier::where('supplier_id', $supplier->id)->where('is_preferred', true)->count());
    }

    public function test_next_months_list_updates_in_place_and_flags_dropped_lines(): void
    {
        (new ReferenceDataSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->upload(self::OCT_HEADER, [
            ['AF', 'AIR FILTER', 'AF213', 'SORENTO 2.5 03-06', '', 'PC'],
            ['CO', 'CONTROL ARMS', 'CO225', 'HYU I10 09- LH', '', 'PC'],
        ], ['supplier_name' => 'Mravat Wholesale']);

        // The February layout: category NAME only, "Application" column, no UOM.
        $this->upload(['Category', 'Item', 'Application', 'Specification'], [
            ['AIR FILTER', 'AF213', 'SORENTO I 2.5CRDi 03-06', ''],
            ['BRAKE DISC', 'BD302', 'RAV4 I 2.0 94-00 (FV)', ''],
        ], ['supplier_name' => 'Mravat Wholesale', 'discontinue_missing' => true])->assertRedirect();

        $import = DataImport::latest('id')->first();
        $this->assertSame(1, $import->created_count, json_encode($import->errors));
        $this->assertSame(1, $import->updated_count);

        $this->assertSame(1, Part::where('part_number', 'AF213')->count());
        $this->assertSame('AIR FILTER - SORENTO I 2.5CRDi 03-06', $this->part('AF213')->description);
        $this->assertSame('BRK-DSC', $this->part('BD302')->category->code);
        $this->assertTrue($this->part('CO225')->is_discontinued);
        $this->assertFalse($this->part('AF213')->is_discontinued);
        $this->assertSame(1, Supplier::where('name', 'Mravat Wholesale')->count());
    }

    public function test_missing_reference_data_is_reported_not_silently_miscategorised(): void
    {
        $this->upload(self::OCT_HEADER, [['AF', 'AIR FILTER', 'AF213', 'SORENTO', '', 'PC']], ['supplier_name' => 'X'])
            ->assertSessionHasErrors('map');
        $this->assertSame(0, Part::count());
    }

    public function test_columns_are_matched_for_both_customer_layouts(): void
    {
        $csv = app(CsvImportService::class);
        $guess = fn (array $header) => collect(SupplierCatalogueImportService::FIELDS)
            ->map(fn ($d, $k) => $csv->guess($header, $d['keywords'], $d['label'], $k))->all();

        $this->assertSame(
            ['part_number' => 2, 'description' => 3, 'category' => 0, 'category_name' => 1, 'specification' => 4, 'unit' => 5, 'brand' => null],
            $guess(self::OCT_HEADER),
        );
        $this->assertSame(
            ['part_number' => 1, 'description' => 2, 'category' => 0, 'category_name' => null, 'specification' => 3, 'unit' => null, 'brand' => null],
            $guess(['Category', 'Item', 'Application', 'Specification']),
        );
    }

    public function test_application_text_becomes_vehicle_fitments_and_brand(): void
    {
        (new ReferenceDataSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->upload(self::OCT_HEADER, [
            ['CH', 'CYLINDER HEAD', 'CH13', 'HILUX 2.4D 2L BARE 88-97', '', 'PC'],
            ['AF', 'AIR FILTER', 'AF213', 'SORENTO I 2.5CRDi 03-06', '', 'PC'],
            ['AL', 'ALTERNATOR', 'AL100', 'AVANZA 1.5', 'BOSCH 12V 70A', 'PC'],
            ['BU', 'BUSHES', 'BU1', 'STABILISER BAR BUSH (5 PER PACK)', '', 'PC'],
        ], ['supplier_name' => 'Mravat Wholesale'])->assertRedirect();

        // Model + engine + year range resolved from trade shorthand.
        $ch13 = Part::where('part_number', 'CH13')->firstOrFail();
        $fit = $ch13->fitments()->sole();
        $this->assertSame('Toyota', $fit->make->name);
        $this->assertSame('Hilux', $fit->model->name);
        $this->assertSame('2L', $fit->engine_code);
        $this->assertSame(1988, $fit->year_from);
        $this->assertSame(1997, $fit->year_to);
        $this->assertSame('catalogue', $fit->source);
        $this->assertFalse($fit->confirmed);

        // Sorento isn't a seeded model → make-level Kia fitment.
        $af213 = Part::where('part_number', 'AF213')->firstOrFail();
        $afFit = $af213->fitments()->sole();
        $this->assertSame('Kia', $afFit->make->name);
        $this->assertNull($afFit->model_id);
        $this->assertSame([2003, 2006], [$afFit->year_from, $afFit->year_to]);

        // A named brand in the text links that brand; everything else is Aftermarket.
        $this->assertSame('Bosch', Part::where('part_number', 'AL100')->firstOrFail()->brand->name);
        $this->assertSame('Aftermarket', $ch13->brand->name);
        $this->assertNotNull($ch13->short_description);

        // Full linkage: scannable barcode, provenance note, supplier's own code
        // preserved on the approved-supplier link (and reachable via part search).
        $this->assertSame('CH13', $ch13->barcode_code128);
        $this->assertSame('Imported: Mravat Wholesale catalogue (CH · CYLINDER HEAD)', $ch13->notes);
        $link = $ch13->approvedSuppliers()->sole();
        $this->assertSame('CH13', $link->supplier_part_number);
        $this->assertTrue($link->is_preferred);

        // No vehicle tokens → no fitments, part still imports fine.
        $this->assertSame(0, Part::where('part_number', 'BU1')->firstOrFail()->fitments()->count());

        // Re-import syncs catalogue fitments instead of duplicating them.
        $this->upload(self::OCT_HEADER, [
            ['CH', 'CYLINDER HEAD', 'CH13', 'HIACE 2.4D 2L BARE 90-95', '', 'PC'],
        ], ['supplier_name' => 'Mravat Wholesale'])->assertRedirect();
        $this->assertSame(1, $ch13->fitments()->where('source', 'catalogue')->count());
        $this->assertSame('HiAce', $ch13->fresh()->fitments()->sole()->model->name);
    }
}
