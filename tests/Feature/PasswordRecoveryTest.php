<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_password_recovery_form(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_password_reset_request_uses_generic_success_message(): void
    {
        User::factory()->create(['email' => 'member@example.com']);

        $this->post(route('password.email'), ['email' => 'member@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If an account matches that email, a reset link has been sent.');
    }

    public function test_reset_form_is_available_for_a_token(): void
    {
        $this->get(route('password.reset', ['token' => 'test-token']))->assertOk();
    }
}
