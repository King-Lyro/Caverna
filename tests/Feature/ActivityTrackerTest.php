<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_lists_active_characters_without_recent_ic_activity(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $stale = $this->character($user, 'Stale', Carbon::now()->subDays(31));
        $recent = $this->character($user, 'Recent', Carbon::now()->subDays(5));

        $this->get(route('activity'))->assertOk()->assertSee($stale->name)->assertDontSee($recent->name);
    }

    private function character(User $user, string $name, Carbon $lastPost): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active', 'last_ic_post_at' => $lastPost]);
    }
}
