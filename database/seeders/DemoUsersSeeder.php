<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demo login per operational role so every screen can be exercised as the
 * person who actually uses it (cashier, buyer, accountant…). Dev/test only.
 * The shared demo password lives here, in the seed file, and nowhere else.
 */
class DemoUsersSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password123';

    private const USERS = [
        ['username' => 'manager', 'name' => 'Tendai Moyo', 'role' => 'Manager'],
        ['username' => 'cashier', 'name' => 'Rudo Chikwanha', 'role' => 'Cashier'],
        ['username' => 'buyer', 'name' => 'Farai Ncube', 'role' => 'Buyer'],
        ['username' => 'accountant', 'name' => 'Nyasha Dube', 'role' => 'Accountant'],
        ['username' => 'workshop', 'name' => 'Blessing Sibanda', 'role' => 'Workshop'],
        ['username' => 'stores', 'name' => 'Tapiwa Mutasa', 'role' => 'Inventory Clerk'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $admin = User::where('username', 'admin')->first();

        foreach (self::USERS as $u) {
            $user = User::firstOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'email' => $u['username'].'@sparespro.local',
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'status' => 'active',
                ]
            );

            $role = Role::where('name', $u['role'])->first();
            if ($role && ! $user->hasRole($u['role'])) {
                $user->roles()->attach($role->id, ['assigned_by' => $admin?->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $this->command?->info('Demo users ready: '.implode(', ', array_column(self::USERS, 'username')).' (see DemoUsersSeeder for the password).');
    }
}
