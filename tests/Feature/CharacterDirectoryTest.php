<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_directory_can_search_by_name(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $this->character($user, 'Ashfall');
        $this->character($user, 'Rainwhisker');

        $this->get(route('characters', ['search' => 'Ashfall']))->assertOk()->assertSee('Ashfall')->assertDontSee('Rainwhisker');
    }

    public function test_character_directory_shows_comparable_character_fields(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->character($user, 'Ashfall');

        $this->get(route('characters'))->assertOk()
            ->assertSee('class="character-directory-table"', false)
            ->assertSee('Sex')->assertSee('Age')->assertSee('Allegiance')
            ->assertSee('Energy')->assertSee('Player')->assertSee($user->name)
            ->assertSee(route('characters.show', $character));
    }

    public function test_inactive_living_characters_remain_visible_but_deceased_are_excluded(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $inactive = $this->character($user, 'Ashfall');
        $inactive->update(['status' => 'inactive', 'energy' => 0]);
        $deceased = $this->character($user, 'Rainwhisker');
        $deceased->update(['status' => 'deceased', 'energy' => 0]);

        $this->get(route('characters'))->assertOk()->assertSee('Ashfall')->assertSee('Inactive')->assertDontSee('Rainwhisker');
    }

    public function test_memorial_has_separate_green_heading_and_character_records(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $remembered = $this->character($user, 'Ashfall');
        $remembered->update(['status' => 'deceased', 'died_at' => now(), 'energy' => 0]);
        $this->character($user, 'Rainwhisker');

        $this->get(route('characters.memorial'))->assertOk()
            ->assertSee('class="page-heading page-heading-theme"', false)
            ->assertSee('class="memorial-content"', false)
            ->assertSee('class="memorial-list"', false)
            ->assertSee('Ashfall')->assertSee($user->name)->assertDontSee('Rainwhisker');
    }

    private function character(User $user, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
