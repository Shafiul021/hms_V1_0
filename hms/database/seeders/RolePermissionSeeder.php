<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the 5 application roles.
     * Permissions can be added here as the system grows.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'admin',
            'doctor',
            'receptionist',
            'nurse',
            'patient',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Roles seeded: ' . implode(', ', $roles));
    }
}
