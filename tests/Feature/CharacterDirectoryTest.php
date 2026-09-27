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

    private function character(User $user, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
