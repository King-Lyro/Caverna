<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_members_can_view_their_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($user->name);
    }

    public function test_pending_members_cannot_view_the_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'pending']);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
