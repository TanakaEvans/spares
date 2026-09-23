<?php

namespace Database\Seeders;

use App\Models\GlPeriod;
use App\Models\GlYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FinancialPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $yearStart = Carbon::now()->startOfYear();

        $year = GlYear::updateOrCreate(
            ['name' => 'FY'.$yearStart->format('Y')],
            [
                'start_date' => $yearStart->toDateString(),
                'end_date' => $yearStart->copy()->endOfYear()->toDateString(),
                'status' => 'open',
            ]
        );

        foreach (range(1, 12) as $month) {
            $start = $yearStart->copy()->month($month)->startOfMonth();

            GlPeriod::updateOrCreate(
                ['year_id' => $year->id, 'period_number' => $month],
                [
                    'name' => $start->format('M Y'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => 'open',
                ]
            );
        }
    }
}
