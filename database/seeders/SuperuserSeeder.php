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

        // Operational roles for a motor-spares business.
        $defaultRoles = [
            ['name' => 'Manager', 'description' => 'Branch manager — full operational access, approvals and overrides'],
            ['name' => 'Cashier', 'description' => 'Point of sale, receipts and daily cash-up'],
            ['name' => 'Buyer', 'description' => 'Purchasing — purchase orders, receiving and supplier management'],
            ['name' => 'Accountant', 'description' => 'Finance — ledger, receipts/payments, VAT and financial reports'],
            ['name' => 'Workshop', 'description' => 'Workshop — job cards, labour, parts issue and invoicing'],
            ['name' => 'Inventory Clerk', 'description' => 'Stock — levels, adjustments, transfers and stock takes'],
        ];

        foreach ($defaultRoles as $role) {
            Role::updateOrCreate(['name' => $role['name']], ['description' => $role['description']]);
        }
        // Retire residue from the template this project started from.
        Role::whereIn('name', ['Kitchen Staff', 'Teacher', 'Student', 'Parent', 'Staff'])->whereDoesntHave('users')->delete();

        // Create Superuser account
        $superuser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@sparespro.local',
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

        // Company identity — Mokone Auto Spares, Bochum, Limpopo (from the business's
        // own branding). Registration/VAT numbers and banking are added under
        // System Admin › Company. Updates the single company record in place.
        $details = [
            'name' => 'Mokone Auto Spares',
            'trading_name' => 'Mokone Auto Spares',
            'email' => 'lcemojela@gmail.com',
            'phone' => '+27 76 472 3635 / +27 73 314 7504',
            'address' => 'Stand No 20, Ga-Tibunyana',
            'city' => 'Bochum',
            'state' => 'Limpopo',
            'country' => 'South Africa',
            'postal_code' => '0790',
            'currency' => 'ZAR',
            'logo' => file_exists(public_path('uploads/company/company_logo.jpg')) ? 'uploads/company/company_logo.jpg' : null,
            'status' => 'active',
        ];
        $company = Company::first();
        if ($company) {
            if ($company->logo || $details['logo'] === null) {
                unset($details['logo']); // never clobber a logo uploaded through the UI
            }
            $company->update($details);
        } else {
            $company = Company::create($details);
        }

        $branchDetails = [
            'company_id' => $company->id,
            'code' => 'BOC-01',
            'name' => 'Bochum Main',
            'address' => 'Stand No 20, Ga-Tibunyana',
            'city' => 'Bochum',
            'phone' => '+27 76 472 3635',
            'email' => 'lcemojela@gmail.com',
            'is_main_branch' => true,
            'status' => 'active',
        ];
        $main = \App\Models\Branch::where('is_main_branch', true)->first();
        $main ? $main->update($branchDetails) : \App\Models\Branch::create($branchDetails);

        // Brand voice on documents and email — only where still at the shipped default.
        $settings = app(\App\Services\SettingsService::class);
        $registry = config('settings_registry'); // flat keys contain dots — index directly, not via config() dot-paths
        foreach ([
            'documents.invoice_footer_text' => 'Quality Parts. Reliable Service. Driven by Trust.',
            'comms.email_from_name' => 'Mokone Auto Spares',
        ] as $key => $value) {
            if ($settings->get($key) == ($registry[$key]['default'] ?? null)) {
                $settings->set($key, $value);
            }
        }

        $this->command->info('Superuser created successfully!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('==================');
        $this->command->info('Username: admin');
        $this->command->info('Password: password123');
        $this->command->info('');
    }
}
