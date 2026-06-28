<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the super-admin user account.
     * Credentials: admin@hms.com / password123
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@hms.com'],
            [
                'name'     => 'HMS Administrator',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign admin role (roles must already be seeded)
        $admin->assignRole('admin');

        $this->command->info('Admin user created: admin@hms.com / password123');
    }
}
