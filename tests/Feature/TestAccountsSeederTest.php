<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_approved_test_and_admin_accounts_with_known_passwords(): void
    {
        $this->seed(TestAccountsSeeder::class);

        $member = User::where('email', 'test@cavernas.test')->firstOrFail();
        $admin = User::where('email', 'admin@cavernas.test')->firstOrFail();

        $this->assertSame('approved', $member->status);
        $this->assertTrue(Hash::check('TestCavernas!2026', $member->password));
        $this->assertTrue($member->hasRole('registered'));
        $this->assertSame('approved', $admin->status);
        $this->assertTrue(Hash::check('AdminCavernas!2026', $admin->password));
        $this->assertTrue($admin->isAdmin());
    }
}