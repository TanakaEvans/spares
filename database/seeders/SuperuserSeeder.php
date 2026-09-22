<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class SuperuserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Superuser role
        $superuserRole = Role::firstOrCreate(
            ['name' => 'Superuser'],
            ['description' => 'Has access to all system functions and routes']
        );

        // Create Admin role
        Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrative access to system settings and user management']
        );

        // Create other default roles
        $defaultRoles = [
            ['name' => 'Manager', 'description' => 'Canteen manager with full operational access'],
            ['name' => 'Cashier', 'description' => 'Point of sale and order processing access'],
            ['name' => 'Kitchen Staff', 'description' => 'Order preparation and kitchen management'],
            ['name' => 'Inventory Clerk', 'description' => 'Stock and inventory management access'],
        ];

        foreach ($defaultRoles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }

        // Create Superuser account
        $superuser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@canteen.com',
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]
        );

        // Assign Superuser role
        if (!$superuser->hasRole('Superuser')) {
            $superuser->roles()->attach($superuserRole->id, [
                'assigned_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default company + main branch
        $company = Company::firstOrCreate(
            ['name' => 'SparesPro Motors'],
            [
                'trading_name' => 'SparesPro Motors',
                'email' => 'info@sparespro.com',
                'phone' => '+263 77 123 4567',
                'address' => '123 Main Street',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'currency' => 'USD',
                'status' => 'active',
            ]
        );

        \App\Models\Branch::firstOrCreate(
            ['code' => 'HRE-01'],
            [
                'company_id' => $company->id,
                'name' => 'Harare Main',
                'city' => 'Harare',
                'is_main_branch' => true,
                'status' => 'active',
            ]
        );

        $this->command->info('Superuser created successfully!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('==================');
        $this->command->info('Username: admin');
        $this->command->info('Password: password123');
        $this->command->info('');
    }
}
