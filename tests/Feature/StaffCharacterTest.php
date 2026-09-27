<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'name' => 'Ashstar', 'role' => 'leader', 'energy' => 100]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'character.updated', 'auditable_id' => $character->id]);
    }

    public function test_members_cannot_access_staff_character_editor(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($user)->get(route('staff.characters.edit', $character))->assertForbidden();
    }
}
