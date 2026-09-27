<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Support\CavernasRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_outsiders_are_excluded_from_clan_population_control(): void
    {
        $user = User::factory()->create();
        for ($index = 0; $index < 5; $index++) {
            Character::create(['user_id' => $user->id, 'name' => 'Rogue '.$index, 'sex' => 'female', 'age_moons' => 20, 'allegiance' => 'Rogue', 'role' => 'rogue', 'looks' => 'A coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        }

        $this->assertTrue(CavernasRules::clanCreationAllowed('ThunderClan'));
    }

    public function test_a_clan_more_than_fifty_percent_above_the_lowest_is_blocked(): void
    {
        $user = User::factory()->create();
        for ($index = 0; $index < 3; $index++) {
            Character::create(['user_id' => $user->id, 'name' => 'Thunder '.$index, 'sex' => 'female', 'age_moons' => 20, 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'A coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        }
        Character::create(['user_id' => $user->id, 'name' => 'River', 'sex' => 'female', 'age_moons' => 20, 'allegiance' => 'RiverClan', 'role' => 'warrior', 'looks' => 'A coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->assertFalse(CavernasRules::clanCreationAllowed('ThunderClan'));
    }
}
