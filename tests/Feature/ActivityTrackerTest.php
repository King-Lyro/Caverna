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

    public function test_tracker_explains_the_rules_and_lists_characters_without_images(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $stale = $this->character($user, 'Ashfall', Carbon::now()->subDays(31));
        $stale->update(['energy' => 40, 'images' => ['https://example.com/ash.jpg']]);

        $this->get(route('activity'))->assertOk()
            ->assertSee('class="activity-list-content"', false)
            ->assertSee('How it works')->assertSee('The list')
            ->assertSee('20 energy')->assertSee('two weeks')
            ->assertSee(route('characters.show', $stale))
            ->assertSee('Last IC post')->assertSee('Joined')
            ->assertDontSee('https://example.com/ash.jpg');
    }

    private function character(User $user, string $name, Carbon $lastPost): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active', 'last_ic_post_at' => $lastPost]);
    }
}
