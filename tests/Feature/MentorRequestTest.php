<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_apprentice_owner_can_request_and_mentor_owner_can_accept(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $mentorOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $mentor = $this->character($mentorOwner, 'Stone', 'warrior');
        $response = $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id]);
        $response->assertRedirect();
        $relationship = CharacterRelationship::firstOrFail();
        $this->actingAs($mentorOwner)->patch(route('characters.mentor-accept', $relationship))->assertRedirect();

        $this->assertDatabaseHas('character_relationships', ['id' => $relationship->id, 'status' => 'accepted']);
        $this->assertTrue($mentor->fresh()->apprentices->contains($apprentice->id));
    }

    public function test_non_mentor_roles_cannot_accept_apprentices(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $mentorOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $kit = $this->character($mentorOwner, 'Kit', 'kit');

        $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $kit), ['apprentice_character_id' => $apprentice->id])->assertStatus(422);
        $this->assertDatabaseCount('character_relationships', 0);
    }

    private function character(User $user, string $name, string $role): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => $role === 'apprentice' ? 6 : 12, 'allegiance' => 'ThunderClan', 'role' => $role, 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
