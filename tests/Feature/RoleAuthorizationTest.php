<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_members_cannot_see_or_access_staff_actions(): void
    {
        $user = User::factory()->create(['role' => 'registered', 'status' => 'approved']);

        $response = $this->actingAs($user)->get(route('staff.applications'));

        $response->assertForbidden();
    }

    public function test_moderators_can_access_staff_review_but_not_admin_character_tools(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator', 'status' => 'approved']);

        $this->actingAs($moderator)->get(route('staff.applications'))->assertOk();
        $this->actingAs($moderator)->get(route('staff.characters'))->assertForbidden();
    }

    public function test_admins_can_access_admin_character_tools(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);

        $this->actingAs($admin)->get(route('staff.characters'))->assertOk();
    }
}
