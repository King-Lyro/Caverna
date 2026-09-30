<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $testUser = $this->account(
            'Test Member',
            'test@cavernas.test',
            env('SEEDED_TEST_PASSWORD', 'TestCavernas!2026'),
            'registered'
        );
        $admin = $this->account(
            'Test Administrator',
            'admin@cavernas.test',
            env('SEEDED_ADMIN_PASSWORD', 'AdminCavernas!2026'),
            'admin'
        );

        if (Schema::hasTable('roles') && Schema::hasTable('user_roles')) {
            $registeredRole = Role::firstOrCreate(['slug' => 'registered'], ['name' => 'Registered', 'description' => 'Approved community members.']);
            $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator', 'description' => 'Members trusted with site administration.']);
            $testUser->roles()->sync([$registeredRole->id]);
            $admin->roles()->sync([$adminRole->id]);
        }
    }

    private function account(string $name, string $email, string $password, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'role' => $role,
            'status' => 'approved',
            'approved_at' => $user->approved_at ?: now(),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        return $user;
    }
}
