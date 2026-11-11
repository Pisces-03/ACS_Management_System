<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Superadmin account
        User::create([
            'employee_id' => 'SUPER001',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@acs.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'branch' => 'las_pinas',
            'position' => 'System Administrator',
            'phone' => '+63-XXX-XXX-XXXX',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Admin account
        User::create([
            'employee_id' => 'ADMIN001',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@laspinas.acs.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch' => 'las_pinas',
            'position' => 'Branch Administrator',
            'phone' => '+63-XXX-XXX-XXXX',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Employee account
        User::create([
            'employee_id' => 'EMP001',
            'first_name' => 'Employee',
            'last_name' => 'User',
            'email' => 'employee@laspinas.acs.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'branch' => 'las_pinas',
            'position' => 'Staff Member',
            'phone' => '+63-XXX-XXX-XXXX',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
