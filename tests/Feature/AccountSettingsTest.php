<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_member_can_view_account_settings(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee($user->email);
    }

    public function test_member_can_update_profile_and_privacy_settings(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'New Name',
            'bio' => 'A short profile.',
            'pronouns' => 'they/them',
            'age' => 30,
            'timezone' => 'America/New_York',
            'hide_personal_info' => '1',
            'is_absent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'hide_personal_info' => true, 'is_absent' => true]);
    }
}
