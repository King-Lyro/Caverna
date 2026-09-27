<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_derives_siblings_and_kits_from_parent_relationships(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $parent = $this->character($other, 'Parent', 20);
        $character = $this->character($user, 'Ash', 0);
        $sibling = $this->character($user, 'Ember', 0);
        $kit = $this->character($user, 'Moss', 0);

        foreach ([
            ['character_id' => $character->id, 'related_character_id' => $parent->id, 'type' => 'parent', 'status' => 'accepted'],
            ['character_id' => $sibling->id, 'related_character_id' => $parent->id, 'type' => 'parent', 'status' => 'accepted'],
            ['character_id' => $kit->id, 'related_character_id' => $character->id, 'type' => 'parent', 'status' => 'accepted'],
        ] as $relationship) {
            CharacterRelationship::create($relationship);
        }

        $this->get(route('characters.show', $character))->assertOk()->assertSee('Ember')->assertSee('Moss');
    }

    private function character(User $user, string $name, float $age): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => $age, 'allegiance' => 'ThunderClan', 'role' => $age < 6 ? 'kit' : 'warrior', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
