<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    /**
     * Seed 20 sample medicines covering common drug categories.
     */
    public function run(): void
    {
        $medicines = [
            // Analgesics / Antipyretics
            ['name' => 'Paracetamol 500mg',     'generic_name' => 'Paracetamol',       'unit' => 'tablet',  'price' => 0.05,  'stock_threshold' => 100],
            ['name' => 'Ibuprofen 400mg',        'generic_name' => 'Ibuprofen',         'unit' => 'tablet',  'price' => 0.10,  'stock_threshold' => 100],
            ['name' => 'Diclofenac 50mg',        'generic_name' => 'Diclofenac Sodium', 'unit' => 'tablet',  'price' => 0.15,  'stock_threshold' => 50],

            // Antibiotics
            ['name' => 'Amoxicillin 500mg',      'generic_name' => 'Amoxicillin',       'unit' => 'capsule', 'price' => 0.20,  'stock_threshold' => 100],
            ['name' => 'Azithromycin 500mg',     'generic_name' => 'Azithromycin',      'unit' => 'tablet',  'price' => 0.80,  'stock_threshold' => 50],
            ['name' => 'Ciprofloxacin 500mg',    'generic_name' => 'Ciprofloxacin',     'unit' => 'tablet',  'price' => 0.30,  'stock_threshold' => 50],
            ['name' => 'Metronidazole 400mg',    'generic_name' => 'Metronidazole',     'unit' => 'tablet',  'price' => 0.10,  'stock_threshold' => 50],

            // Antihypertensives
            ['name' => 'Amlodipine 5mg',         'generic_name' => 'Amlodipine',        'unit' => 'tablet',  'price' => 0.12,  'stock_threshold' => 50],
            ['name' => 'Lisinopril 10mg',        'generic_name' => 'Lisinopril',        'unit' => 'tablet',  'price' => 0.18,  'stock_threshold' => 50],
            ['name' => 'Atenolol 50mg',          'generic_name' => 'Atenolol',          'unit' => 'tablet',  'price' => 0.08,  'stock_threshold' => 50],

            // Antidiabetics
            ['name' => 'Metformin 500mg',        'generic_name' => 'Metformin HCl',     'unit' => 'tablet',  'price' => 0.06,  'stock_threshold' => 100],
            ['name' => 'Glibenclamide 5mg',      'generic_name' => 'Glibenclamide',     'unit' => 'tablet',  'price' => 0.05,  'stock_threshold' => 50],

            // Gastrointestinal
            ['name' => 'Omeprazole 20mg',        'generic_name' => 'Omeprazole',        'unit' => 'capsule', 'price' => 0.25,  'stock_threshold' => 50],
            ['name' => 'Ranitidine 150mg',       'generic_name' => 'Ranitidine',        'unit' => 'tablet',  'price' => 0.10,  'stock_threshold' => 50],
            ['name' => 'Domperidone 10mg',       'generic_name' => 'Domperidone',       'unit' => 'tablet',  'price' => 0.08,  'stock_threshold' => 50],

            // Antihistamines
            ['name' => 'Cetirizine 10mg',        'generic_name' => 'Cetirizine HCl',   'unit' => 'tablet',  'price' => 0.10,  'stock_threshold' => 50],
            ['name' => 'Loratadine 10mg',        'generic_name' => 'Loratadine',        'unit' => 'tablet',  'price' => 0.12,  'stock_threshold' => 50],

            // IV / Infusions
            ['name' => 'Normal Saline 0.9% 500ml', 'generic_name' => 'Sodium Chloride', 'unit' => 'bag',    'price' => 2.50,  'stock_threshold' => 20],
            ['name' => 'Dextrose 5% 500ml',      'generic_name' => 'Dextrose',          'unit' => 'bag',     'price' => 2.00,  'stock_threshold' => 20],

            // Vitamins
            ['name' => 'Vitamin C 500mg',        'generic_name' => 'Ascorbic Acid',     'unit' => 'tablet',  'price' => 0.05,  'stock_threshold' => 100],
        ];

        foreach ($medicines as $medicine) {
            Medicine::firstOrCreate(
                ['name' => $medicine['name']],
                $medicine
            );
        }

        $this->command->info(count($medicines) . ' medicines seeded.');
    }
}
