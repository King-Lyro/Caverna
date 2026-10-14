<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_members_cannot_create_threads(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->post(route('forum.thread.store', $board), [
            'title' => 'A first trail',
            'body' => str_repeat('A story word ', 20),
        ]);

        $response->assertForbidden();
    }

    public function test_guests_can_only_post_to_advertisements_and_keep_a_guest_identity(): void
    {
        $category = ForumCategory::create(['name' => 'Community', 'sort_order' => 1]);
        $ads = ForumBoard::where('slug', 'advertisements')->firstOrFail();
        $other = $category->boards()->create(['name' => 'Questions', 'slug' => 'questions', 'is_ic' => false]);
        $this->get(route('forum'))->assertOk()->assertSee('Advertisements')->assertSee('paw_icon.png');

        $this->get(route('forum.thread.create', $ads))->assertOk()->assertSee('guest_name')
            ->assertSee('What does 2+2 equal? (Type 22 if you are human.)')
            ->assertDontSee('Leave this field empty')->assertDontSee('name="website"', false);
        $this->get(route('forum.thread.create', $other))->assertRedirect(route('login'));
        $this->post(route('forum.thread.store', $other), ['title' => 'Hello', 'body' => 'A guest post.'])->assertForbidden();

        $this->post(route('forum.thread.store', $ads), [
            'title' => 'A guest advert',
            'body' => 'A guest post.',
            'guest_name' => 'Pinecone',
            'human_answer' => '22',
        ])->assertRedirect();
        $thread = $ads->threads()->firstOrFail();
        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id, 'user_id' => null, 'guest_name' => 'Pinecone']);
        $this->assertDatabaseHas('forum_posts', ['forum_thread_id' => $thread->id, 'user_id' => null, 'guest_name' => 'Pinecone']);
        $this->get(route('forum.thread', $thread))->assertOk()->assertSee('Pinecone')->assertSee('Guest')
            ->assertSee('What does 2+2 equal? (Type 22 if you are human.)')
            ->assertDontSee('Leave this field empty')->assertDontSee('name="website"', false);
        $this->post(route('forum.reply.store', $thread), ['guest_name' => 'Birch', 'body' => 'A guest reply.', 'human_answer' => '22'])->assertRedirect();
        $this->assertDatabaseHas('forum_posts', ['forum_thread_id' => $thread->id, 'guest_name' => 'Birch', 'user_id' => null]);
    }

    public function test_guest_threads_and_replies_require_the_instructed_answer_not_the_math_answer(): void
    {
        $ads = ForumBoard::where('slug', 'advertisements')->firstOrFail();
        $data = ['title' => 'Guest advertisement', 'guest_name' => 'Pine', 'body' => 'A guest post.'];
        $this->post(route('forum.thread.store', $ads), $data)->assertSessionHasErrors('human_answer');
        $this->post(route('forum.thread.store', $ads), $data + ['human_answer' => '4'])->assertSessionHasErrors('human_answer');
        $this->assertDatabaseCount('forum_threads', 0);

        $owner = User::factory()->create(['status' => 'approved']);
        $thread = $ads->threads()->create(['user_id' => $owner->id, 'title' => 'Community', 'slug' => 'community']);
        $this->post(route('forum.reply.store', $thread), $data)->assertSessionHasErrors('human_answer');
        $this->post(route('forum.reply.store', $thread), $data + ['human_answer' => '4'])->assertSessionHasErrors('human_answer');
        $this->assertDatabaseCount('forum_posts', 0);

        $this->actingAs($owner)->get(route('forum.thread.create', $ads))->assertOk()->assertDontSee('name="human_answer"', false);
        $this->post(route('forum.thread.store', $ads), ['title' => 'Member advert', 'body' => 'A member post.'])->assertRedirect();
        $this->post(route('forum.reply.store', $thread), ['body' => 'A member reply.'])->assertRedirect();
        $this->assertDatabaseCount('forum_posts', 2);
    }

    public function test_members_may_reply_to_announcements_but_cannot_create_threads_there(): void
    {
        $category = ForumCategory::create(['name' => 'News', 'sort_order' => 1]);
        $announcements = $category->boards()->create(['name' => 'Announcements', 'slug' => 'announcements', 'is_ic' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $thread = $announcements->threads()->create(['user_id' => $admin->id, 'title' => 'News', 'slug' => 'news']);
        $member = User::factory()->create(['status' => 'approved']);

        $this->actingAs($member)->post(route('forum.thread.store', $announcements), [
            'title' => 'Another announcement',
            'body' => 'Not allowed.',
        ])->assertForbidden();
        $this->actingAs($member)->post(route('forum.reply.store', $thread), [
            'body' => 'Thanks for the update.',
        ])->assertRedirect();
        $this->assertDatabaseHas('forum_posts', ['forum_thread_id' => $thread->id, 'user_id' => $member->id]);
    }

    public function test_ic_threads_require_seventy_words(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);

        $response = $this->actingAs($user)->post(route('forum.thread.store', $board), [
            'title' => 'A short trail',
            'body' => 'Too short.',
            'character_id' => $character->id,
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_seventy_characters_are_not_enough_for_an_ic_post(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);

        $this->actingAs($user)->post(route('forum.thread.store', $board), [
            'title' => 'A short scene', 'body' => str_repeat('Quiet paw steps. ', 18), 'character_id' => $character->id,
        ])->assertSessionHasErrors('body');
    }

    public function test_approved_member_can_create_a_thread_and_opening_post(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);
        $body = str_repeat('The morning wind carried the scent of rain through the sleeping hollow. ', 7);

        $response = $this->actingAs($user)->post(route('forum.thread.store', $board), [
            'title' => 'A new trail',
            'body' => $body,
            'character_id' => $character->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forum_threads', ['title' => 'A new trail', 'user_id' => $user->id]);
        $this->assertDatabaseHas('forum_posts', ['user_id' => $user->id, 'is_ic' => true]);
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $user->id, 'amount' => 20, 'type' => 'post_thread']);
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'energy' => 90]);
    }

    public function test_ic_replies_earn_ten_crickets_and_ooc_posts_earn_none(): void
    {
        $category = ForumCategory::create(['name' => 'Stories', 'sort_order' => 1]);
        $icBoard = $category->boards()->create(['name' => 'Territory', 'slug' => 'territory', 'is_ic' => true]);
        $oocBoard = $category->boards()->create(['name' => 'Chat', 'slug' => 'chat', 'is_ic' => false]);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);
        $body = str_repeat('The morning wind carried the scent of rain through the sleeping hollow. ', 7);

        $this->actingAs($user)->post(route('forum.thread.store', $icBoard), ['title' => 'A new trail', 'body' => $body, 'character_id' => $character->id])->assertRedirect();
        $thread = $icBoard->threads()->firstOrFail();
        $replyPage = $this->get(route('forum.thread', $thread))->assertOk();
        $this->assertLessThan(
            strpos($replyPage->getContent(), 'Continue the thread'),
            strpos($replyPage->getContent(), '<label>Character<select name="character_id"'),
        );
        $replyPage->assertSee('class="thread-pagination"', false);
        $this->actingAs($user)->post(route('forum.reply.store', $thread), ['body' => $body, 'character_id' => $character->id])->assertRedirect();
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $user->id, 'amount' => 20, 'type' => 'post_thread']);
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $user->id, 'amount' => 10, 'type' => 'post_reply']);

        $this->actingAs($user)->post(route('forum.thread.store', $oocBoard), ['title' => 'Out of character', 'body' => 'Hello everyone.'])->assertRedirect();
        $this->actingAs($user)->post(route('forum.reply.store', $oocBoard->threads()->firstOrFail()), ['body' => 'Welcome!'])->assertRedirect();
        $this->assertDatabaseCount('cricket_ledger', 2);
    }

    public function test_inactive_character_can_post_at_home_to_regain_energy_but_deceased_cannot(): void
    {
        $category = ForumCategory::create(['name' => 'Clan camps', 'sort_order' => 1]);
        $board = $category->boards()->create(['name' => 'ThunderClan Camp', 'slug' => 'thunderclan-camp', 'is_ic' => true]);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);
        $character->update(['energy' => 0, 'status' => 'inactive', 'inactive_at' => now()]);
        $payload = ['title' => 'Home again', 'body' => str_repeat('The camp welcomes another careful voice back to the changing story. ', 7), 'character_id' => $character->id];

        $this->actingAs($user)->post(route('forum.thread.store', $board), $payload)->assertRedirect();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'status' => 'active', 'energy' => 10, 'inactive_at' => null]);

        $character->update(['status' => 'deceased', 'energy' => 0]);
        $this->actingAs($user)->post(route('forum.thread.store', $board), $payload)->assertSessionHas('error');
    }

    public function test_enemy_post_that_exhausts_energy_marks_character_inactive(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);
        $character->update(['energy' => 10]);

        $this->actingAs($user)->post(route('forum.thread.store', $board), [
            'title' => 'A tiring visit', 'body' => str_repeat('The morning wind carried the scent of rain through the sleeping hollow. ', 7), 'character_id' => $character->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'energy' => 0, 'status' => 'inactive']);
        $this->assertNotNull($character->fresh()->inactive_at);
    }

    public function test_home_and_foreign_territory_posts_adjust_energy_by_ten(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $home = ForumCategory::create(['name' => 'ThunderClan territory', 'territory' => 'ThunderClan', 'sort_order' => 1])
            ->boards()->create(['name' => 'Camp', 'slug' => 'home-camp', 'is_ic' => true]);
        $away = ForumCategory::create(['name' => 'RiverClan territory', 'territory' => 'RiverClan', 'sort_order' => 2])
            ->boards()->create(['name' => 'Camp', 'slug' => 'away-camp', 'is_ic' => true]);
        $character = $this->makeCharacter($user);
        $character->update(['energy' => 80]);
        $payload = ['title' => 'At home', 'body' => str_repeat('The morning wind carried the scent of rain through the sleeping hollow. ', 7), 'character_id' => $character->id];

        $this->actingAs($user)->post(route('forum.thread.store', $home), $payload)->assertRedirect();
        $this->assertSame(90, $character->fresh()->energy);
        $this->actingAs($user)->post(route('forum.thread.store', $away), [...$payload, 'title' => 'Away'])->assertRedirect();
        $this->assertSame(80, $character->fresh()->energy);
    }

    private function makeBoard(bool $isIc): ForumBoard
    {
        $category = ForumCategory::create(['name' => 'Stories', 'sort_order' => 1]);

        return $category->boards()->create([
            'name' => $isIc ? 'Territory' : 'Chat',
            'slug' => $isIc ? 'territory' : 'chat',
            'description' => 'A test board.',
            'is_ic' => $isIc,
        ]);
    }

    private function makeCharacter(User $user): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
