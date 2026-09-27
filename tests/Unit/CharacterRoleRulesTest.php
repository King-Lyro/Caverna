<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterLifecycleService;
use App\Support\CavernasRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CharacterRoleRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_bands_follow_age_for_clan_cats(): void
    {
        $this->assertSame('kit', CavernasRules::roleForAge(5.5, 'ThunderClan'));
        $this->assertSame('apprentice', CavernasRules::roleForAge(6, 'ThunderClan'));
        $this->assertSame('warrior', CavernasRules::roleForAge(12, 'ThunderClan'));
        $this->assertSame('elder', CavernasRules::roleForAge(130, 'ThunderClan'));
    }

    public function test_lifecycle_archives_a_cat_at_161_moons(): void
    {
        $character = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'Oldstone', 'sex' => 'male', 'age_moons' => 160.5, 'allegiance' => 'ThunderClan', 'role' => 'elder', 'looks' => 'Silver coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 40, 'status' => 'active']);

        $updated = app(CharacterLifecycleService::class)->advanceWeek($character, Carbon::parse('2026-09-27'));

        $this->assertSame('deceased', $updated->status);
        $this->assertSame(0, $updated->energy);
        $this->assertNotNull($updated->archived_at);
    }

    public function test_frozen_characters_do_not_age_or_lose_energy(): void
    {
        $character = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'Stillwater', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'RiverClan', 'role' => 'warrior', 'looks' => 'Blue eyes.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 40, 'status' => 'active', 'is_frozen' => true]);

        $updated = app(CharacterLifecycleService::class)->advanceWeek($character, Carbon::parse('2026-09-27'));

        $this->assertSame(12.0, (float) $updated->age_moons);
        $this->assertSame(40, $updated->energy);
    }

    public function test_a_kit_becomes_an_apprentice_at_six_moons(): void
    {
        $character = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'Newleaf', 'sex' => 'female', 'age_moons' => 5.5, 'allegiance' => 'ThunderClan', 'role' => 'kit', 'looks' => 'Small paws.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'Born in Cavernas.', 'energy' => 100, 'status' => 'active']);

        $updated = app(CharacterLifecycleService::class)->advanceWeek($character, Carbon::parse('2026-09-27'));

        $this->assertSame(6.0, (float) $updated->age_moons);
        $this->assertSame('apprentice', $updated->role);
    }
}
