<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create default users for each branch and role

        // Superadmin
        User::create([
            'employee_id' => 'SA001',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@acs.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'branch' => 'las_pinas',
            'position' => 'System Administrator',
            'phone' => '+63917-123-4567',
            'is_active' => true,
        ]);

        // Las Piñas Branch
        User::create([
            'employee_id' => 'LP001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'admin@laspinas.acs.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch' => 'las_pinas',
            'position' => 'Branch Manager',
            'phone' => '+63917-123-4568',
            'is_active' => true,
        ]);

        User::create([
            'employee_id' => 'LP002',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'email' => 'employee@laspinas.acs.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'branch' => 'las_pinas',
            'position' => 'Maintenance Technician',
            'phone' => '+63917-123-4569',
            'is_active' => true,
        ]);

        // Cebu Branch
        User::create([
            'employee_id' => 'CB001',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'admin@cebu.acs.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch' => 'cebu',
            'position' => 'Branch Manager',
            'phone' => '+63917-123-4570',
            'is_active' => true,
        ]);

        User::create([
            'employee_id' => 'CB002',
            'first_name' => 'Pedro',
            'last_name' => 'Garcia',
            'email' => 'employee@cebu.acs.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'branch' => 'cebu',
            'position' => 'Senior Technician',
            'phone' => '+63917-123-4571',
            'is_active' => true,
        ]);

        // Davao Branch
        User::create([
            'employee_id' => 'DV001',
            'first_name' => 'Rosa',
            'last_name' => 'Torres',
            'email' => 'admin@davao.acs.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch' => 'davao',
            'position' => 'Branch Manager',
            'phone' => '+63917-123-4572',
            'is_active' => true,
        ]);

        User::create([
            'employee_id' => 'DV002',
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'email' => 'employee@davao.acs.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'branch' => 'davao',
            'position' => 'Maintenance Specialist',
            'phone' => '+63917-123-4573',
            'is_active' => true,
        ]);

        echo "Database seeded successfully!\n";
        echo "Login credentials:\n";
        echo "Superadmin: admin@acs.com / password\n";
        echo "Las Piñas Admin: admin@laspinas.acs.com / password\n";
        echo "Las Piñas Employee: employee@laspinas.acs.com / password\n";
        echo "Cebu Admin: admin@cebu.acs.com / password\n";
        echo "Cebu Employee: employee@cebu.acs.com / password\n";
        echo "Davao Admin: admin@davao.acs.com / password\n";
        echo "Davao Employee: employee@davao.acs.com / password\n";
    }
}
