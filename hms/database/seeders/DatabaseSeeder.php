<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters:
     *   1. RolePermissionSeeder  — roles must exist before users
     *   2. AdminUserSeeder       — assigns the admin role
     *   3. LabTestSeeder         — master catalog (no dependencies)
     *   4. WardBedSeeder         — ward + bed data (no dependencies)
     *   5. MedicineSeeder        — medicine inventory (no dependencies)
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            LabTestSeeder::class,
            WardBedSeeder::class,
            MedicineSeeder::class,
        ]);
    }
}

