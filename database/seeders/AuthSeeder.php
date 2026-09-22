<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            ['name' => 'Superuser', 'description' => 'Has complete access to all system functions'],
            ['name' => 'Admin', 'description' => 'Administrative access to most system functions'],
            ['name' => 'Teacher', 'description' => 'Access to teaching functions and classroom management'],
            ['name' => 'Student', 'description' => 'Access to student portal and academic information'],
            ['name' => 'Parent', 'description' => 'Access to parent portal and child information'],
            ['name' => 'Staff', 'description' => 'Access to administrative functions'],
        ];

        foreach ($roles as $role) {
            DB::table('auth_roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Create superuser
        $superuserId = DB::table('auth_users')->insertGetId([
            'name' => 'System Administrator',
            'email' => 'admin@schoolportal.com',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign superuser role
        $superuserRoleId = DB::table('auth_roles')->where('name', 'Superuser')->value('id');
        DB::table('auth_user_roles')->updateOrInsert(
            ['user_id' => $superuserId, 'role_id' => $superuserRoleId],
            [
                'user_id' => $superuserId,
                'role_id' => $superuserRoleId,
                'assigned_by' => $superuserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create demo users
        $demoUsers = [
            ['name' => 'John Smith', 'email' => 'teacher@schoolportal.com', 'username' => 'teacher001', 'role' => 'Teacher'],
            ['name' => 'Jane Doe', 'email' => 'student@schoolportal.com', 'username' => 'student001', 'role' => 'Student'],
            ['name' => 'Robert Johnson', 'email' => 'parent@schoolportal.com', 'username' => 'parent001', 'role' => 'Parent'],
            ['name' => 'Mary Wilson', 'email' => 'staff@schoolportal.com', 'username' => 'staff001', 'role' => 'Staff'],
        ];

        foreach ($demoUsers as $userData) {
            $userId = DB::table('auth_users')->insertGetId([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleId = DB::table('auth_roles')->where('name', $userData['role'])->value('id');
            DB::table('auth_user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $superuserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
