<?php

namespace Database\Seeders;

use App\Models\LabourCode;
use Illuminate\Database\Seeder;

/**
 * Workshop labour pack: common operations with standard times and VAT-exclusive
 * rand rates typical for an independent Limpopo workshop. Adjust the rates
 * under Workshop › Labour Codes to match your pricing. No technicians are
 * seeded — add your real staff. Idempotent.
 */
class WorkshopSeeder extends Seeder
{
    public function run(): void
    {
        // [code, description, category, rate type, standard hours, rate (R/hour excl. VAT)]
        $codes = [
            ['SVC-MIN-01', 'Minor service (oil & filter)', 'Servicing', 'flat_rate', 1.00, 450.00],
            ['SVC-MAJ-02', 'Major service', 'Servicing', 'flat_rate', 2.50, 450.00],
            ['BRK-PAD-01', 'Front brake pads replacement', 'Brakes', 'flat_rate', 1.00, 480.00],
            ['BRK-PAD-02', 'Rear brake pads / shoes replacement', 'Brakes', 'flat_rate', 1.20, 480.00],
            ['BRK-DSC-01', 'Brake discs skim / replace (pair)', 'Brakes', 'flat_rate', 1.50, 480.00],
            ['ENG-TB-01', 'Timing belt / chain replacement', 'Engine', 'flat_rate', 3.50, 520.00],
            ['ENG-CLT-01', 'Clutch replacement', 'Transmission', 'flat_rate', 4.00, 520.00],
            ['SUS-SHK-01', 'Shock absorbers replacement (pair)', 'Suspension', 'flat_rate', 1.50, 480.00],
            ['SUS-CV-01', 'CV joint / driveshaft replacement', 'Suspension', 'flat_rate', 1.50, 480.00],
            ['ELE-BAT-01', 'Battery test & replacement', 'Electrical', 'flat_rate', 0.30, 450.00],
            ['ELE-ALT-01', 'Alternator / starter replacement', 'Electrical', 'flat_rate', 1.50, 520.00],
            ['COOL-RAD-01', 'Radiator / water pump replacement', 'Cooling', 'flat_rate', 2.00, 480.00],
            ['AC-REGAS-01', 'Air-con regas', 'Air-conditioning', 'fixed_price', 0.75, 650.00],
            ['TYR-FIT-01', 'Tyre fitting & balancing (per wheel)', 'Tyres', 'fixed_price', 0.25, 120.00],
            ['WHL-ALN-01', 'Wheel alignment', 'Tyres', 'fixed_price', 0.75, 450.00],
            ['DIAG-01', 'Diagnostic scan', 'Diagnostics', 'fixed_price', 0.50, 650.00],
            ['GEN-LAB-01', 'General labour (per hour)', 'General', 'actual_time', 1.00, 450.00],
        ];

        foreach ($codes as [$code, $desc, $cat, $type, $hours, $rate]) {
            LabourCode::updateOrCreate(['code' => $code], [
                'description' => $desc, 'category' => $cat, 'rate_type' => $type,
                'standard_hours' => $hours, 'default_rate' => $rate, 'is_active' => true,
            ]);
        }

        $this->command?->info(count($codes).' workshop labour codes seeded (rand, VAT-exclusive).');
    }
}
