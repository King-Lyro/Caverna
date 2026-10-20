<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Models\User;
use App\Support\TerritoryMap;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MapForumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientBugFixesTest extends TestCase
{
    use RefreshDatabase;

    private function character(User $user, string $name, array $attributes = []): Character
    {
        return Character::create($attributes + [
            'user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 24,
            'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'Bright eyes.',
            'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.',
            'energy' => 100, 'status' => 'active',
        ]);
    }

    public function test_profile_shows_empty_relationships_without_removal_controls(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $cat = $this->character($owner, 'Ash');
        $this->actingAs($owner)->get(route('characters.show', $cat))->assertOk()
            ->assertSee('Mating')->assertSee('Mentor')->assertSee('Apprentices')
            ->assertDontSee('Remove mate')->assertDontSee('Remove mentor')->assertDontSee('Remove apprentice');
    }

    public function test_either_mate_owner_can_end_the_pairing_without_approval_and_notify_the_other(): void
    {
        foreach ([0, 1] as $initiator) {
            $owners = [User::factory()->create(['status' => 'approved']), User::factory()->create(['status' => 'approved'])];
            $cats = [$this->character($owners[0], 'Ash'), $this->character($owners[1], 'Rain')];
            foreach ([0, 1] as $i) {
                $cats[$i]->mates()->attach($cats[1 - $i], ['accepted_at' => now()]);
                $cats[$i]->update(['mate' => $cats[1 - $i]->name]);
            }
            $this->actingAs(User::factory()->create(['status' => 'approved']))
                ->get(route('characters.show', $cats[$initiator]))->assertOk()->assertDontSee('Remove mate');
            $this->actingAs($owners[$initiator])->get(route('characters.show', $cats[$initiator]))->assertOk()->assertSee('Remove mate');
            $this->delete(route('characters.mate-remove', $cats[$initiator]))->assertSessionHasNoErrors()->assertRedirect();
            foreach ($cats as $cat) {
                $this->assertNull($cat->fresh()->mate);
                $this->assertFalse($cat->mates()->exists());
            }
            $notification = $owners[1 - $initiator]->notifications()->firstOrFail();
            $this->assertSame('Mate relationship ended', $notification->data['title']);
            $this->assertSame(route('characters.show', $cats[1 - $initiator]), $notification->data['url']);
            $this->assertSame(0, $owners[$initiator]->notifications()->count());
        }
    }

    public function test_unrelated_members_cannot_remove_mates_and_legacy_text_can_be_cleared(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $cat = $this->character($owner, 'Ash', ['mate' => 'Old legacy mate']);
        $this->actingAs($other)->delete(route('characters.mate-remove', $cat))->assertForbidden();
        $this->assertSame('Old legacy mate', $cat->fresh()->mate);
        $this->actingAs($owner)->delete(route('characters.mate-remove', $cat))->assertRedirect();
        $this->assertNull($cat->fresh()->mate);
        $this->delete(route('characters.mate-remove', $cat))->assertSessionHas('error');
    }

    public function test_either_side_can_remove_mentorship_and_request_again(): void
    {
        foreach ([0, 1] as $initiator) {
            $owners = [User::factory()->create(['status' => 'approved']), User::factory()->create(['status' => 'approved'])];
            $apprentice = $this->character($owners[0], 'Ashpaw', ['role' => 'apprentice', 'age_moons' => 8]);
            $mentor = $this->character($owners[1], 'Rainfur');
            $relationship = CharacterRelationship::create(['character_id' => $apprentice->id, 'related_character_id' => $mentor->id, 'type' => 'mentor', 'status' => 'accepted', 'accepted_at' => now()]);
            $this->actingAs(User::factory()->create(['status' => 'approved']))
                ->get(route('characters.show', $apprentice))->assertOk()->assertDontSee('Remove mentor');
            $this->actingAs($owners[$initiator])->get(route('characters.show', $initiator ? $mentor : $apprentice))->assertOk()
                ->assertSee(route('characters.mentor-remove', $relationship))
                ->assertSee($initiator ? 'Remove apprentice</button>' : 'Remove mentor</button>', false)
                ->assertDontSee('Remove apprentice:')->assertDontSee('Remove mentor:');
            $this->delete(route('characters.mentor-remove', $relationship))->assertSessionHasNoErrors()->assertRedirect();
            $this->assertModelMissing($relationship);
            $this->assertFalse($apprentice->mentors()->exists());
            $this->assertFalse($mentor->hasCurrentApprentice());
            $this->assertSame('Mentorship ended', $owners[1 - $initiator]->notifications()->firstOrFail()->data['title']);
            $this->actingAs($owners[0])->post(route('characters.mentor-request', $mentor), ['apprentice_character_id' => $apprentice->id])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('character_relationships', ['character_id' => $apprentice->id, 'related_character_id' => $mentor->id, 'status' => 'pending']);
        }
    }

    public function test_removal_protects_other_owners_parent_records_and_pending_requests(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $stranger = User::factory()->create(['status' => 'approved']);
        $cat = $this->character($owner, 'Ash');
        $related = $this->character($other, 'Rain');
        $relationship = CharacterRelationship::create(['character_id' => $cat->id, 'related_character_id' => $related->id, 'type' => 'mentor', 'status' => 'accepted']);
        $this->actingAs($stranger)->delete(route('characters.mentor-remove', $relationship))->assertForbidden();
        $this->assertModelExists($relationship);
        $relationship->update(['type' => 'parent']);
        $this->actingAs($owner)->delete(route('characters.mentor-remove', $relationship))->assertSessionHas('error');
        $this->assertModelExists($relationship);
        $relationship->update(['type' => 'mentor', 'status' => 'pending']);
        $this->delete(route('characters.mentor-remove', $relationship))->assertSessionHas('error');
        $this->assertModelExists($relationship);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_medicine_mentorship_can_be_ended_by_either_member(): void
    {
        foreach ([0, 1] as $initiator) {
            $owners = [User::factory()->create(['status' => 'approved']), User::factory()->create(['status' => 'approved'])];
            $apprentice = $this->character($owners[0], 'Herbpaw', ['role' => 'medicine_cat_apprentice']);
            $mentor = $this->character($owners[1], 'Herbleaf', ['role' => 'medicine_cat']);
            $relationship = CharacterRelationship::create(['character_id' => $apprentice->id, 'related_character_id' => $mentor->id, 'type' => 'mentor', 'status' => 'accepted']);
            $this->actingAs($owners[$initiator])->delete(route('characters.mentor-remove', $relationship))->assertSessionHasNoErrors();
            $this->assertModelMissing($relationship);
            $this->assertSame('Mentorship ended', $owners[1 - $initiator]->notifications()->firstOrFail()->data['title']);
        }
    }

    public function test_member_profiles_and_ooc_posts_use_uploaded_or_default_member_avatars(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        $this->assertFileExists(public_path('images/profile_default.png'));
        $this->get(route('members.show', $member))->assertOk()->assertSee(asset('images/profile_default.png'), false);
        $board = ForumBoard::where('slug', 'advertisements')->firstOrFail();
        $thread = $board->threads()->create(['user_id' => $member->id, 'title' => 'Community', 'slug' => 'community']);
        $thread->posts()->create(['user_id' => $member->id, 'body' => 'An OOC post.', 'is_ic' => false]);
        $this->get(route('forum.thread', $thread))->assertOk()->assertSee(asset('images/profile_default.png'), false);
        $member->update(['avatar_path' => 'avatars/member.png']);
        $this->get(route('members.show', $member))->assertOk()->assertSee(asset('storage/avatars/member.png'), false)->assertDontSee(asset('images/profile_default.png'), false);
        $this->get(route('forum.thread', $thread))->assertOk()->assertSee(asset('storage/avatars/member.png'), false);
        $cat = $this->character($member, 'Ash', ['forum_avatar_path' => 'characters/ash.png']);
        $thread->posts()->firstOrFail()->update(['character_id' => $cat->id]);
        $this->get(route('forum.thread', $thread))->assertOk()->assertSee(asset('storage/avatars/member.png'), false)->assertDontSee(asset('storage/characters/ash.png'), false);
    }

    public function test_ic_posts_keep_the_character_portrait_instead_of_member_avatar(): void
    {
        $member = User::factory()->create(['status' => 'approved', 'avatar_path' => 'avatars/member.png']);
        $cat = $this->character($member, 'Ash', ['forum_avatar_path' => 'characters/ash.png']);
        $category = ForumCategory::create(['name' => 'Stories']);
        $board = $category->boards()->create(['name' => 'Forest', 'slug' => 'forest', 'is_ic' => true]);
        $thread = $board->threads()->create(['user_id' => $member->id, 'title' => 'Story', 'slug' => 'story']);
        $thread->posts()->create(['user_id' => $member->id, 'character_id' => $cat->id, 'body' => 'An IC story.', 'is_ic' => true]);
        $this->get(route('forum.thread', $thread))->assertOk()->assertSee(asset('storage/characters/ash.png'), false)->assertDontSee(asset('storage/avatars/member.png'), false);
    }

    public function test_directory_role_counts_match_live_clan_population_and_ignore_search(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        $this->character($member, 'Active warrior');
        $this->character($member, 'Inactive warrior', ['status' => 'inactive']);
        $this->character($member, 'Apprentice', ['role' => 'apprentice', 'age_moons' => 8]);
        $this->character($member, 'Dead warrior', ['status' => 'deceased']);
        $this->character($member, 'Medicine cat', ['role' => 'medicine_cat']);
        $this->character($member, 'Outsider warrior', ['allegiance' => 'outsider']);
        $this->get(route('characters', ['search' => 'Apprentice']))->assertOk()
            ->assertSee('2 warriors')->assertSee('1 apprentice')->assertSee('0 warriors')->assertSee('0 apprentices');
    }

    public function test_territory_forums_match_the_client_screenshots_and_are_idempotent(): void
    {
        $this->seed(MapForumSeeder::class);
        $this->seed(MapForumSeeder::class);
        $expected = [
            'ThunderClan Territory' => ['ThunderClan Camp', 'Twoleg Path', 'Oak Forest', 'Marshes', 'Caves'],
            'WindClan Territory' => ['WindClan Camp', 'Rapid Falls', 'Moor', 'Leaping Boulders', 'Mist Beach'],
            'RiverClan Territory' => ['RiverClan Camp', 'Twoleg Path', 'Marshland', 'Placid River', 'Pond', 'Roaring River'],
            'ShadowClan Territory' => ['ShadowClan Camp', 'Twoleg Path', 'Pine Forest', 'Leaping Rocks', 'Splashing River', 'Rock Bridge'],
            'Outsider Land' => ['Twoleg Path', 'Twoleg Dens', 'Mountains', 'Mint Island', 'Moose Island', 'Mouse Island', 'The Great Ledge', 'Moonspring'],
        ];
        foreach ($expected as $name => $boards) {
            $category = ForumCategory::where('name', $name)->firstOrFail();
            $this->assertSame($boards, $category->boards()->orderBy('sort_order')->pluck('name')->all());
        }
        $this->assertDatabaseMissing('forum_categories', ['name' => 'outsider Territory']);
        $this->assertSame(30, ForumBoard::where('is_ic', true)->count());
        $this->assertSame(32, collect(TerritoryMap::locations())->sum(fn ($location) => count($location['areas'])));
    }

    public function test_forum_corrections_preserve_threads_and_retire_only_the_requested_item(): void
    {
        $this->seed(MapForumSeeder::class);
        $member = User::factory()->create(['status' => 'approved']);
        $legacy = ForumCategory::create(['name' => 'outsider Territory', 'territory' => 'outsider']);
        $oldBoard = $legacy->boards()->create(['name' => 'Twoleg Path', 'slug' => 'old-twoleg-path', 'is_ic' => true]);
        $thread = $oldBoard->threads()->create(['user_id' => $member->id, 'title' => 'Old path story', 'slug' => 'old-path-story']);
        $post = $thread->posts()->create(['user_id' => $member->id, 'body' => 'Keep this story.', 'is_ic' => true]);
        $moss = ShopItem::create(['name' => 'Moss poultice', 'slug' => 'moss-poultice', 'description' => 'Old item', 'cost' => 100, 'effect' => 'Energy restoration', 'is_available' => true]);
        Inventory::create(['user_id' => $member->id, 'shop_item_id' => $moss->id, 'quantity' => 2]);
        $energy = ShopItem::create(['name' => 'Energy return', 'slug' => 'energy-return', 'description' => 'Energy', 'cost' => 20, 'is_available' => true]);
        (require database_path('migrations/2026_10_21_000002_apply_client_forum_and_shop_fixes.php'))->up();
        $this->assertModelMissing($legacy);
        $this->assertModelMissing($oldBoard);
        $this->assertSame(ForumBoard::where('slug', 'twoleg-path')->firstOrFail()->id, $thread->fresh()->forum_board_id);
        $this->assertModelExists($post);
        $this->assertFalse($moss->fresh()->is_available);
        $this->assertTrue($energy->fresh()->is_available);
        $this->assertDatabaseHas('inventories', ['shop_item_id' => $moss->id, 'quantity' => 2]);
        $this->actingAs($member)->get(route('shop'))->assertOk()->assertDontSee('Moss poultice')->assertSee('Energy return');
        $this->post(route('shop.purchase', $moss))->assertNotFound();
    }

    public function test_fresh_seed_has_no_moss_poultice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseMissing('shop_items', ['slug' => 'moss-poultice']);
    }

    public function test_exit_preview_clears_session_and_provides_tab_close_hook(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->withSession(['theme_preview' => 'winter'])->get(route('home'))->assertOk()->assertSee('data-close-preview', false);
        $this->post(route('admin.themes.preview.stop'))->assertRedirect(route('admin.themes.index'))->assertSessionMissing('theme_preview');
    }
}
