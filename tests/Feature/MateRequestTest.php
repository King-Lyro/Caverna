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

        $this->actingAs($user)->post(route('characters.mate-request', $character), ['from_character_id' => $character->id])->assertRedirect()->assertSessionHas('error', 'A character cannot request its own mate.');
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

    public function test_pending_pairing_requests_are_counted_for_both_owners_and_link_to_decisions(): void
    {
        $sender = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $from = $this->character($sender, 'Ember');
        $to = $this->character($recipient, 'Rain');
        $this->actingAs($sender)->post(route('characters.mate-request', $to), ['from_character_id' => $from->id])->assertRedirect();
        $request = MateRequest::firstOrFail();
        $notification = $recipient->notifications()->firstOrFail();

        $this->assertSame(route('dashboard').'#breeding-requests', $notification->data['url']);
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Breeding requests</span><strong>1</strong>', false)
            ->assertSee('Sent · Awaiting')
            ->assertDontSee(route('characters.mate-reject', $request));
        $this->actingAs($recipient)->get(route('dashboard'))->assertOk()
            ->assertSee('Breeding requests</span><strong>1</strong>', false)
            ->assertSee(route('characters.show', $from))
            ->assertSee(route('characters.show', $to))
            ->assertSee(route('characters.mate-accept', $request))
            ->assertSee(route('characters.mate-reject', $request))
            ->assertSee('id="breeding-requests"', false);
        $this->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard').'#breeding-requests');
        $this->patch(route('characters.mate-reject', $request))->assertRedirect();
        $this->assertDatabaseHas('mate_requests', ['id' => $request->id, 'status' => 'rejected']);
        $this->assertNull($from->fresh()->mate);
    }

    public function test_an_ordinary_character_under_twelve_moons_cannot_request_a_mate(): void
    {
        $sender = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $from = $this->character($sender, 'Ember');
        $from->update(['age_moons' => 11.5]);
        $to = $this->character($recipient, 'Rain');

        $this->actingAs($sender)->post(route('characters.mate-request', $to), ['from_character_id' => $from->id])->assertRedirect()->assertSessionHas('error', 'Both characters must be at least 12 moons old to take a mate.');
    }

    public function test_apprentices_and_underage_recipients_cannot_be_mated(): void
    {
        $sender = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $from = $this->character($sender, 'Ember');
        $to = $this->character($recipient, 'Rain');

        $from->update(['age_moons' => 11, 'role' => 'apprentice']);
        $this->actingAs($sender)->post(route('characters.mate-request', $to), ['from_character_id' => $from->id])->assertSessionHas('error');

        $from->update(['age_moons' => 12, 'role' => 'warrior']);
        $to->update(['age_moons' => 11, 'role' => 'apprentice']);
        $this->actingAs($sender)->post(route('characters.mate-request', $to), ['from_character_id' => $from->id])->assertSessionHas('error');
        $this->assertDatabaseCount('mate_requests', 0);
    }

    private function character(User $user, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
