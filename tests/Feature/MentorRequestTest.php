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
        $notification = $mentorOwner->notifications()->firstOrFail();
        $this->assertSame('Mentor request', $notification->data['title']);
        $this->assertSame(route('dashboard').'#mentor-requests', $notification->data['url']);
        $this->actingAs($mentorOwner)->get(route('dashboard'))->assertOk()
            ->assertSee('id="mentor-requests"', false)->assertSee(route('characters.mentor-accept', $relationship));
        $this->actingAs($mentorOwner)->patch(route('characters.mentor-accept', $relationship))->assertRedirect();

        $this->assertDatabaseHas('character_relationships', ['id' => $relationship->id, 'status' => 'accepted']);
        $this->assertTrue($mentor->fresh()->apprentices->contains($apprentice->id));
        $this->assertSame('Mentor request accepted', $apprenticeOwner->notifications()->firstOrFail()->data['title']);
    }

    public function test_non_mentor_roles_cannot_accept_apprentices(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $mentorOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $kit = $this->character($mentorOwner, 'Kit', 'kit');

        $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $kit), ['apprentice_character_id' => $apprentice->id])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('character_relationships', 0);
    }

    public function test_medicine_cats_cannot_be_requested_as_mentors_by_members(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $medicineCatOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $medicineCat = $this->character($medicineCatOwner, 'Willow', 'medicine_cat');

        $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $medicineCat), ['apprentice_character_id' => $apprentice->id])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('character_relationships', 0);
    }

    public function test_apprentices_cannot_request_a_mentor_from_another_allegiance(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $mentorOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $mentor = $this->character($mentorOwner, 'Stone', 'warrior');
        $mentor->update(['allegiance' => 'RiverClan']);

        $this->actingAs($apprenticeOwner)
            ->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('character_relationships', 0);
    }

    public function test_deceased_apprentice_cannot_request_and_frozen_mentor_cannot_accept(): void
    {
        $apprenticeOwner = User::factory()->create(['status' => 'approved']);
        $mentorOwner = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($apprenticeOwner, 'Ash', 'apprentice');
        $mentor = $this->character($mentorOwner, 'Stone', 'warrior');
        $apprentice->update(['status' => 'deceased']);

        $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertSessionHas('error');
        $this->assertDatabaseCount('character_relationships', 0);

        $apprentice->update(['status' => 'active']);
        $this->actingAs($apprenticeOwner)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertRedirect();
        $mentor->update(['is_frozen' => true]);
        $this->actingAs($mentorOwner)->patch(route('characters.mentor-accept', CharacterRelationship::firstOrFail()))->assertSessionHas('error');
    }

    private function character(User $user, string $name, string $role): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => $role === 'apprentice' ? 6 : 12, 'allegiance' => 'ThunderClan', 'role' => $role, 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }

    public function test_leaders_deputies_and_warriors_can_receive_member_requests(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        foreach (['leader', 'deputy', 'warrior'] as $role) {
            $apprentice = $this->character($owner, 'Ash '.$role, 'apprentice');
            $mentor = $this->character($other, 'Stone '.$role, $role);
            $this->actingAs($owner)->get(route('characters.show', $mentor))->assertOk()->assertSee('Request mentorship for');
            $this->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertRedirect();
            $relationship = CharacterRelationship::where('character_id', $apprentice->id)->firstOrFail();
            $this->actingAs($other)->patch(route('characters.mentor-accept', $relationship))->assertRedirect();
            $this->assertSame('accepted', $relationship->fresh()->status);
        }
    }

    public function test_acceptance_rechecks_roles_allegiance_and_pregnancy(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($owner, 'Ash', 'apprentice');
        $mentor = $this->character($other, 'Stone', 'warrior');
        $this->actingAs($owner)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertRedirect();
        $relationship = CharacterRelationship::firstOrFail();
        $this->actingAs($other);
        foreach (['medicine_cat', 'queen', 'kit'] as $role) {
            $mentor->update(['role' => $role]);
            $this->patch(route('characters.mentor-accept', $relationship))->assertSessionHas('error');
            $this->assertSame('pending', $relationship->fresh()->status);
        }
        $mentor->update(['role' => 'warrior', 'allegiance' => 'RiverClan']);
        $this->patch(route('characters.mentor-accept', $relationship))->assertSessionHas('error');
        $mentor->update(['allegiance' => 'ThunderClan']);
        $apprentice->update(['role' => 'warrior']);
        $this->patch(route('characters.mentor-accept', $relationship))->assertSessionHas('error');
        $apprentice->update(['role' => 'apprentice']);
        $male = $this->character($owner, 'Briar', 'warrior');
        \App\Models\Pregnancy::create([
            'female_character_id' => $mentor->id, 'male_character_id' => $male->id,
            'conceived_at' => now(), 'due_at' => now()->addWeeks(4), 'status' => 'pregnant',
        ]);
        $this->patch(route('characters.mentor-accept', $relationship))->assertSessionHas('error');
        $this->assertSame('pending', $relationship->fresh()->status);
    }

    public function test_only_apprentice_owner_can_request_and_only_other_owner_can_accept(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($owner, 'Ash', 'apprentice');
        $mentor = $this->character($other, 'Stone', 'warrior');
        $this->actingAs($other)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertNotFound();
        $this->actingAs($owner)->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertRedirect();
        $relationship = CharacterRelationship::firstOrFail();
        $this->patch(route('characters.mentor-accept', $relationship))->assertForbidden();
        $this->assertSame('pending', $relationship->fresh()->status);
    }

    public function test_an_apprentice_cannot_accept_a_second_mentor(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $apprentice = $this->character($owner, 'Ash', 'apprentice');
        $mentor = $this->character($other, 'Stone', 'warrior');
        $another = $this->character($other, 'Rain', 'leader');
        foreach ([$mentor, $another] as $candidate) {
            $this->actingAs($owner)->post(route('characters.mentor-request', $candidate), ['apprentice_character_id' => $apprentice->id])->assertRedirect();
        }
        $requests = CharacterRelationship::orderBy('id')->get();
        $this->actingAs($other)->patch(route('characters.mentor-accept', $requests[0]))->assertRedirect();
        $this->patch(route('characters.mentor-accept', $requests[1]))->assertSessionHas('error');
        $this->assertSame('pending', $requests[1]->fresh()->status);
        $this->assertCount(1, $apprentice->fresh()->mentors);
    }
}
