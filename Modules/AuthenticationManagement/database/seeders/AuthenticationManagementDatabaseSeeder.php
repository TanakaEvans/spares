<?php

namespace Modules\AuthenticationManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AuthenticationManagement\Models\User;
use Modules\AuthenticationManagement\Models\Role;

class AuthenticationManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperUserSeeder::class,
        ]);
    }
}

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Superuser',
                'description' => 'Has complete access to all system functions and can manage all users and settings.'
            ],
            [
                'name' => 'Admin',
                'description' => 'Administrative access to most system functions with user management capabilities.'
            ],
            [
                'name' => 'Teacher',
                'description' => 'Access to teaching functions, classroom management, and student academic records.'
            ],
            [
                'name' => 'Student',
                'description' => 'Access to student portal, academic information, and learning resources.'
            ],
            [
                'name' => 'Parent',
                'description' => 'Access to parent portal, child academic information, and communication tools.'
            ],
            [
                'name' => 'Staff',
                'description' => 'Access to administrative functions related to their department and responsibilities.'
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                ['description' => $roleData['description']]
            );
        }
    }
}

class SuperUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default superuser
        $user = User::firstOrCreate(
            ['email' => 'admin@schoolportal.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Assign Superuser role
        $superuserRole = Role::where('name', 'Superuser')->first();
        if ($superuserRole && !$user->hasRole('Superuser')) {
            $user->assignRole('Superuser');
        }

        // Create demo users for each role
        $this->createDemoUsers();
    }

    private function createDemoUsers(): void
    {
        $demoUsers = [
            [
                'name' => 'John Smith',
                'email' => 'teacher@schoolportal.com',
                'username' => 'teacher001',
                'role' => 'Teacher'
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'student@schoolportal.com',
                'username' => 'student001',
                'role' => 'Student'
            ],
            [
                'name' => 'Robert Johnson',
                'email' => 'parent@schoolportal.com',
                'username' => 'parent001',
                'role' => 'Parent'
            ],
            [
                'name' => 'Mary Wilson',
                'email' => 'staff@schoolportal.com',
                'username' => 'staff001',
                'role' => 'Staff'
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
