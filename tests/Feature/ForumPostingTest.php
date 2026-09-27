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

    public function test_approved_member_can_create_a_thread_and_opening_post(): void
    {
        $board = $this->makeBoard(true);
        $user = User::factory()->create(['status' => 'approved']);
        $character = $this->makeCharacter($user);
        $body = str_repeat('The morning wind carried the scent of rain through the sleeping hollow. ', 4);

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
