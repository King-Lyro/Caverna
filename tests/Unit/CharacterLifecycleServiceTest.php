<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CharacterLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_advance_week_ages_and_removes_energy_after_inactivity(): void
    {
        $character = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'Dawn', 'sex' => 'female', 'age_moons' => 6, 'allegiance' => 'ThunderClan', 'looks' => 'Bright eyes.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 40, 'status' => 'active', 'last_ic_post_at' => Carbon::parse('2026-09-01')]);
        $updated = app(CharacterLifecycleService::class)->advanceWeek($character, Carbon::parse('2026-09-15'));

        $this->assertSame(6.5, (float) $updated->age_moons);
        $this->assertSame(20, $updated->energy);
        $this->assertSame('active', $updated->status);
    }

    public function test_zero_energy_characters_become_inactive_then_deceased_after_grace_period(): void
    {
        $character = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'Dusk', 'sex' => 'male', 'age_moons' => 6, 'allegiance' => 'ShadowClan', 'looks' => 'Dark coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 20, 'status' => 'active', 'last_ic_post_at' => Carbon::parse('2026-09-01')]);
        $service = app(CharacterLifecycleService::class);
        $inactive = $service->advanceWeek($character, Carbon::parse('2026-09-15'));
        $deceased = $service->advanceWeek($inactive->fresh(), Carbon::parse('2026-10-01'));

        $this->assertSame('inactive', $inactive->status);
        $this->assertSame('deceased', $deceased->status);
    }

    public function test_litter_size_stays_inside_the_requested_bounds(): void
    {
        $service = app(CharacterLifecycleService::class);
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->assertGreaterThanOrEqual(1, $service->litterSize(true, true));
            $this->assertLessThanOrEqual(12, $service->litterSize(true, true));
            $this->assertLessThanOrEqual(6, $service->litterSize(false, false));
        }
    }
}
