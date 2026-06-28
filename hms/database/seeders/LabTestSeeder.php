<?php

namespace Database\Seeders;

use App\Models\LabTest;
use Illuminate\Database\Seeder;

class LabTestSeeder extends Seeder
{
    /**
     * Seed the master lab test catalog with common tests.
     */
    public function run(): void
    {
        $tests = [
            [
                'name'              => 'Complete Blood Count',
                'code'              => 'CBC',
                'price'             => 15.00,
                'turnaround_hours'  => 4,
            ],
            [
                'name'              => 'Urinalysis',
                'code'              => 'UA',
                'price'             => 10.00,
                'turnaround_hours'  => 2,
            ],
            [
                'name'              => 'Fasting Blood Sugar',
                'code'              => 'FBS',
                'price'             => 12.00,
                'turnaround_hours'  => 2,
            ],
            [
                'name'              => 'Liver Function Test',
                'code'              => 'LFT',
                'price'             => 35.00,
                'turnaround_hours'  => 8,
            ],
            [
                'name'              => 'Renal Function Test',
                'code'              => 'RFT',
                'price'             => 30.00,
                'turnaround_hours'  => 8,
            ],
            [
                'name'              => 'Lipid Profile',
                'code'              => 'LIPID',
                'price'             => 40.00,
                'turnaround_hours'  => 6,
            ],
            [
                'name'              => 'Thyroid Function Test',
                'code'              => 'TFT',
                'price'             => 45.00,
                'turnaround_hours'  => 12,
            ],
            [
                'name'              => 'HbA1c (Glycated Haemoglobin)',
                'code'              => 'HBA1C',
                'price'             => 25.00,
                'turnaround_hours'  => 6,
            ],
            [
                'name'              => 'Blood Culture',
                'code'              => 'BC',
                'price'             => 50.00,
                'turnaround_hours'  => 48,
            ],
            [
                'name'              => 'Chest X-Ray',
                'code'              => 'CXR',
                'price'             => 60.00,
                'turnaround_hours'  => 1,
            ],
        ];

        foreach ($tests as $test) {
            LabTest::firstOrCreate(['code' => $test['code']], $test);
        }

        $this->command->info(count($tests) . ' lab tests seeded.');
    }
}
