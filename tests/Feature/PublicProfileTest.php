<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_member_profile_shows_public_bio_and_living_characters(): void
    {
        $member = User::factory()->create(['status' => 'approved', 'bio' => 'A little about me.', 'pronouns' => 'She/her', 'location' => 'Sample location', 'avatar_path' => 'avatars/member.png']);
        $character = Character::create([
            'user_id' => $member->id,
            'name' => 'Ash',
            'sex' => 'female',
            'age_moons' => 12,
            'allegiance' => 'ThunderClan',
            'role' => 'warrior',
            'looks' => 'A careful gaze.',
            'appearance' => 'Appearance.',
            'personality' => 'Personality.',
            'history' => 'History.',
            'energy' => 100,
            'status' => 'active',
        ]);

        $this->get(route('members.show', $member))->assertOk()
            ->assertSee('A little about me.')
            ->assertSee(route('characters.show', $character))
            ->assertSee('ThunderClan')->assertSee('avatars/member.png')
            ->assertSee('She/her')->assertSee('Sample location')
            ->assertSee('IC posts')->assertSee('OOC posts')
            ->assertSee('member-character-card')->assertDontSee('Achievements')
            ->assertSee('<h1>'.e($member->name).'</h1>', false);
    }

    public function test_private_member_information_is_not_shown_publicly(): void
    {
        $member = User::factory()->create([
            'status' => 'approved',
            'bio' => 'This is private.',
            'hide_personal_info' => true,
        ]);

        $this->get(route('members.show', $member))->assertOk()
            ->assertSee('chosen to keep personal information private')
            ->assertDontSee('This is private.');
    }

    public function test_pending_members_do_not_have_public_profiles(): void
    {
        $member = User::factory()->create(['status' => 'pending']);

        $this->get(route('members.show', $member))->assertNotFound();
    }

    public function test_staff_can_follow_pending_member_links_from_the_user_panel(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $member = User::factory()->create(['status' => 'pending']);
        $this->actingAs($staff)->get(route('admin.users.index'))->assertOk()
            ->assertSee(route('members.show', $member))
            ->assertSee(route('admin.users.crickets.adjust', $member))
            ->assertSee('form="user-permissions"', false)
            ->assertDontSee('Currency controls');
        $this->get(route('members.show', $member))->assertOk()->assertSee($member->name);
    }
}
