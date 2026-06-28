<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Ward;
use Hms\Core\Enums\BedStatus;
use Illuminate\Database\Seeder;

class WardBedSeeder extends Seeder
{
    /**
     * Seed wards and beds.
     * Uses BedStatus enum from hms-core.
     */
    public function run(): void
    {
        $wards = [
            [
                'name'       => 'General Ward',
                'type'       => 'general',
                'capacity'   => 20,
                'daily_rate' => 50.00,
                'beds'       => 20,
            ],
            [
                'name'       => 'ICU',
                'type'       => 'icu',
                'capacity'   => 8,
                'daily_rate' => 300.00,
                'beds'       => 8,
            ],
            [
                'name'       => 'Private Ward',
                'type'       => 'private',
                'capacity'   => 10,
                'daily_rate' => 150.00,
                'beds'       => 10,
            ],
        ];

        foreach ($wards as $wardData) {
            $bedCount = $wardData['beds'];
            unset($wardData['beds']);

            $ward = Ward::firstOrCreate(
                ['name' => $wardData['name']],
                $wardData
            );

            // Seed beds for this ward
            for ($i = 1; $i <= $bedCount; $i++) {
                $bedNumber = strtoupper(substr($ward->type, 0, 1)) . str_pad($i, 2, '0', STR_PAD_LEFT);

                Bed::firstOrCreate(
                    ['ward_id' => $ward->id, 'bed_number' => $bedNumber],
                    ['status'  => BedStatus::Available]
                );
            }
        }

        $this->command->info('Wards and beds seeded (General: 20 beds, ICU: 8 beds, Private: 10 beds).');
    }
}
