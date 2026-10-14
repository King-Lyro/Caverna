<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_age_attestation_and_stores_its_timestamp(): void
    {
        $payload = [
            'name' => 'New Member',
            'email' => 'new-member@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roleplay_sample' => str_repeat('A thoughtful roleplay sample. ', 3),
        ];

        $this->post(route('register'), $payload)->assertSessionHasErrors('age_confirmed');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);

        $this->post(route('register'), [...$payload, 'age_confirmed' => '1'])->assertRedirect(route('login'));
        $member = User::where('email', $payload['email'])->firstOrFail();
        $this->assertNotNull($member->age_confirmed_at);
    }
}
