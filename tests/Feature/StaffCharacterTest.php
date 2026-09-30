<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StaffCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_character_role_and_energy_with_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $owner = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $owner->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 40, 'status' => 'active']);

        $this->actingAs($admin)->patch(route('staff.characters.update', $character), ['name' => 'Ashstar', 'allegiance' => 'ThunderClan', 'role' => 'leader', 'status' => 'active', 'energy' => 100])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'name' => 'Ashstar', 'role' => 'leader', 'role_locked' => true, 'energy' => 100]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'character.updated', 'auditable_id' => $character->id]);
    }

    public function test_members_cannot_access_staff_character_editor(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($user)->get(route('staff.characters.edit', $character))->assertForbidden();
    }

    public function test_staff_can_correct_age_and_profile_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $owner = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $owner->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($admin)->patch(route('staff.characters.update', $character), [
            'name' => 'Ash', 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'status' => 'active', 'energy' => 100,
            'age_moons' => 14.5, 'sex' => 'male', 'history' => 'Staff corrected the character history.',
        ])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'age_moons' => 14.5, 'sex' => 'male', 'history' => 'Staff corrected the character history.']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $character->id, 'action' => 'character.updated']);
    }

    public function test_admin_can_assign_medicine_cat_apprentice_to_medicine_cat(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $owner = User::factory()->create(['status' => 'approved']);
        $apprentice = Character::create(['user_id' => $owner->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 8, 'allegiance' => 'ThunderClan', 'role' => 'medicine_cat_apprentice', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $mentor = Character::create(['user_id' => $owner->id, 'name' => 'Stone', 'sex' => 'male', 'age_moons' => 20, 'allegiance' => 'ThunderClan', 'role' => 'medicine_cat', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($owner)->post(route('staff.characters.medicine-mentor', $apprentice), ['mentor_id' => $mentor->id])->assertForbidden();
        $this->actingAs($admin)->post(route('staff.characters.medicine-mentor', $apprentice), ['mentor_id' => $mentor->id])->assertRedirect();
        $this->assertDatabaseHas('character_relationships', ['character_id' => $apprentice->id, 'related_character_id' => $mentor->id, 'type' => 'mentor', 'status' => 'accepted']);
    }

    public function test_staff_can_replace_character_portraits_and_forum_avatar(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $owner = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $owner->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($admin)->patch(route('staff.characters.update', $character), [
            'name' => 'Ash', 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'status' => 'active', 'energy' => 100,
            'image_urls' => "https://example.com/one.jpg\nhttps://example.com/two.jpg\nhttps://example.com/three.jpg",
            'forum_avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
        ])->assertRedirect();

        $this->assertCount(3, $character->fresh()->images);
        $this->assertNotNull($character->fresh()->forum_avatar_path);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $character->id, 'action' => 'character.updated']);
    }
}
