<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\MateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_request_their_character_as_its_own_mate(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->character($user, 'Ember');

        $this->actingAs($user)->post(route('characters.mate-request', $character), ['from_character_id' => $character->id])->assertStatus(422);
    }

    public function test_mate_request_can_be_accepted_by_the_recipient_owner(): void
    {
        $sender = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $from = $this->character($sender, 'Ember');
        $to = $this->character($recipient, 'Rain');

        $this->actingAs($sender)->post(route('characters.mate-request', $to), ['from_character_id' => $from->id])->assertRedirect();
        $request = MateRequest::firstOrFail();
        $this->actingAs($recipient)->patch(route('characters.mate-accept', $request))->assertRedirect();

        $this->assertDatabaseHas('mate_requests', ['id' => $request->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('characters', ['id' => $from->id, 'mate' => 'Rain']);
        $this->assertDatabaseHas('characters', ['id' => $to->id, 'mate' => 'Ember']);
    }

    private function character(User $user, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
