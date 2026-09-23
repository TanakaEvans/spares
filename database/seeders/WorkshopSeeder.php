<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\LabourCode;
use App\Models\Technician;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

/**
 * Workshop master data: a common labour-code pack, two technicians, and a demo
 * vehicle for the seeded trade customer. Idempotent. Rates are VAT-exclusive.
 */
class WorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['ENG-OIL-01', 'Minor Service (oil & filter)', 'Servicing', 'flat_rate', 0.80, 25.00],
            ['ENG-SVC-02', 'Major Service', 'Servicing', 'flat_rate', 2.50, 25.00],
            ['BRK-PAD-01', 'Front Brake Pads Replacement', 'Brakes', 'flat_rate', 1.20, 28.00],
            ['ENG-TB-01', 'Timing Belt Replacement', 'Engine', 'flat_rate', 3.50, 30.00],
            ['DIAG-01', 'Diagnostic Scan', 'Diagnostics', 'fixed_price', 0.50, 20.00],
            ['GEN-LAB-01', 'General Labour (per hour)', 'General', 'actual_time', 1.00, 22.00],
        ];

        foreach ($codes as [$code, $desc, $cat, $type, $hours, $rate]) {
            LabourCode::updateOrCreate(['code' => $code], [
                'description' => $desc, 'category' => $cat, 'rate_type' => $type,
                'standard_hours' => $hours, 'default_rate' => $rate, 'is_active' => true,
            ]);
        }

        Technician::updateOrCreate(['name' => 'Tendai Moyo'], [
            'skill_level' => 'qualified', 'specialisations' => 'Engines, Diagnostics', 'cost_rate' => 8.00, 'is_active' => true,
        ]);
        Technician::updateOrCreate(['name' => 'Farai Ncube'], [
            'skill_level' => 'master', 'specialisations' => 'Transmissions, Brakes', 'cost_rate' => 12.00, 'is_active' => true,
        ]);

        // Demo vehicle for the seeded trade customer (needs SalesSeeder + reference data).
        $customer = Customer::where('customer_number', 'TRADE-001')->first();
        $toy = VehicleMake::where('code', 'TOY')->first();
        $hilux = $toy ? VehicleModel::where('make_id', $toy->id)->where('name', 'Hilux')->first() : null;

        if ($customer) {
            CustomerVehicle::updateOrCreate(['registration' => 'AEK-1234'], [
                'customer_id' => $customer->id,
                'make_id' => $toy?->id,
                'model_id' => $hilux?->id,
                'year' => 2019,
                'engine_code' => '2GD-FTV',
                'colour' => 'White',
                'is_active' => true,
            ]);
        }

        $this->command?->info('Workshop labour codes, technicians and a demo vehicle seeded.');
    }
}
