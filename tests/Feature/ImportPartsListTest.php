<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\PartCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * spares:import-parts-list loads a simple vehicle parts list into the catalogue,
 * composing a searchable description from the vehicle/capacity/year/part-name
 * columns. Handles the "BREAK PADS" shape (part name under a second part-number
 * header + a position column) and the "TIE ROD ENDS" shape (a Part Name column).
 */
class ImportPartsListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        UnitOfMeasure::create(['name' => 'Each', 'abbreviation' => 'ea']);
    }

    private function csv(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'pl').'.csv';
        file_put_contents($file, $content);

        return $file;
    }

    public function test_brake_pads_shape_with_position_column(): void
    {
        $file = $this->csv(implode("\n", [
            'PART NUMBER,VIHICLE,CAPACITY,YEAR,PART NUMBER,',
            'D3117,FIESTA/MAZDA,,1997,BREAK PADS,FRONT',
            'D993,BMW,330i,1989-1994,BREAK PADS,FRONT',
        ]));

        $this->artisan('spares:import-parts-list', ['file' => $file, '--category' => 'Brakes'])->assertSuccessful();
        @unlink($file);

        $part = Part::where('part_number', 'D3117')->firstOrFail();
        $this->assertSame('BREAK PADS FRONT - FIESTA/MAZDA 1997', $part->description);
        $this->assertSame('Brakes', $part->category->name);

        $bmw = Part::where('part_number', 'D993')->firstOrFail();
        $this->assertSame('BREAK PADS FRONT - BMW 330i 1989-1994', $bmw->description);
    }

    public function test_tie_rod_shape_with_part_name_column(): void
    {
        $file = $this->csv(implode("\n", [
            'PART NUMBER,VIHICLE,CAPACITY,YEAR,PART NAME',
            'TR5484,MITSUSHI COLD,LDV,1997,TIE RODS ENDS',
        ]));

        $this->artisan('spares:import-parts-list', ['file' => $file, '--category' => 'Steering'])->assertSuccessful();
        @unlink($file);

        $part = Part::where('part_number', 'TR5484')->firstOrFail();
        $this->assertSame('TIE RODS ENDS - MITSUSHI COLD LDV 1997', $part->description);
    }

    public function test_re_run_is_idempotent_and_keeps_existing_descriptions(): void
    {
        $existing = Part::factory()->create([
            'part_number' => 'D3117',
            'description' => 'ORIGINAL CATALOGUE DESC',
            'category_id' => PartCategory::create(['name' => 'Old', 'code' => 'OLD', 'is_active' => true, 'sort_order' => 1])->id,
        ]);

        $file = $this->csv("PART NUMBER,VIHICLE,CAPACITY,YEAR,PART NUMBER,\nD3117,FIESTA/MAZDA,,1997,BREAK PADS,FRONT\n");
        $this->artisan('spares:import-parts-list', ['file' => $file, '--category' => 'Brakes'])->assertSuccessful();
        @unlink($file);

        $existing->refresh();
        $this->assertSame('ORIGINAL CATALOGUE DESC', $existing->description); // not clobbered
        $this->assertSame(1, Part::where('part_number', 'D3117')->count());   // not duplicated
    }
}
