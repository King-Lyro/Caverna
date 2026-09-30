<?php

namespace Tests\Feature;

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_owner_can_edit_their_own_post(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $thread = $this->thread();
        $post = $thread->posts()->create(['user_id' => $user->id, 'body' => 'Original out of character message text here.', 'is_ic' => false]);

        $this->actingAs($user)->patch(route('forum.post.update', $post), ['body' => 'Updated out of character message text here.'])->assertRedirect();

        $this->assertDatabaseHas('forum_posts', ['id' => $post->id, 'body' => 'Updated out of character message text here.']);
        $this->assertNotNull($post->fresh()->edited_at);
    }

    public function test_other_members_cannot_edit_someone_elses_post(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $thread = $this->thread();
        $post = $thread->posts()->create(['user_id' => $owner->id, 'body' => 'Original message text here for the post.', 'is_ic' => false]);

        $this->actingAs($other)->patch(route('forum.post.update', $post), ['body' => 'Hijacked message text here for the post.'])->assertForbidden();
    }

    public function test_thread_renders_owner_actions_and_hides_staff_panel_from_members(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $thread = $this->thread();
        $thread->posts()->create(['user_id' => $owner->id, 'body' => 'Original message text here for the post.', 'is_ic' => false]);

        $this->actingAs($owner)->get(route('forum.thread', $thread))->assertOk()
            ->assertSee('data-post-edit-open', false)
            ->assertSee('data-post-report-open', false)
            ->assertSee('data-post-edit-panel', false)
            ->assertSee('data-post-report-panel', false)
            ->assertDontSee('staff-tools-panel', false);
    }

    public function test_thread_renders_staff_actions_only_for_staff(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $thread = $this->thread();
        $thread->posts()->create(['user_id' => User::factory()->create(['status' => 'approved'])->id, 'body' => 'Original message text here for the post.', 'is_ic' => false]);

        $this->actingAs($staff)->get(route('forum.thread', $thread))->assertOk()
            ->assertSee('staff-tools-panel', false)
            ->assertSee('Lock thread')
            ->assertSee('Pin thread')
            ->assertSee('Move to board')
            ->assertSee('Delete post');
    }

    public function test_staff_can_lock_pin_move_and_delete_a_thread(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $thread = $this->thread();
        $otherBoard = ForumCategory::first()->boards()->create(['name' => 'Elsewhere', 'slug' => 'elsewhere', 'is_ic' => true]);

        $this->actingAs($staff)->patch(route('forum.thread.lock', $thread))->assertRedirect();
        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id, 'is_locked' => true]);

        $this->actingAs($staff)->patch(route('forum.thread.sticky', $thread))->assertRedirect();
        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id, 'is_pinned' => true]);

        $this->actingAs($staff)->patch(route('forum.thread.move', $thread), ['forum_board_id' => $otherBoard->id])->assertRedirect();
        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id, 'forum_board_id' => $otherBoard->id]);

        $this->actingAs($staff)->delete(route('forum.thread.destroy', $thread))->assertRedirect();
        $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
    }

    public function test_staff_can_delete_a_post_and_edit_any_members_post(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $member = User::factory()->create(['status' => 'approved']);
        $thread = $this->thread();
        $firstPost = $thread->posts()->create(['user_id' => $member->id, 'body' => 'The opening post for this story.', 'is_ic' => false]);
        $secondPost = $thread->posts()->create(['user_id' => $member->id, 'body' => 'A reply someone else wrote here.', 'is_ic' => false]);

        $this->actingAs($staff)->patch(route('forum.post.update', $secondPost), ['body' => 'Staff edited this reply for the rules.'])->assertRedirect();
        $this->assertDatabaseHas('forum_posts', ['id' => $secondPost->id, 'body' => 'Staff edited this reply for the rules.']);

        $this->actingAs($staff)->delete(route('forum.post.destroy', $secondPost))->assertRedirect();
        $this->assertSoftDeleted('forum_posts', ['id' => $secondPost->id]);
    }

    public function test_non_staff_cannot_use_staff_forum_actions(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        $thread = $this->thread();

        $this->actingAs($member)->patch(route('forum.thread.lock', $thread))->assertForbidden();
        $this->actingAs($member)->delete(route('forum.thread.destroy', $thread))->assertForbidden();
    }

    private function thread(): ForumThread
    {
        $category = ForumCategory::create(['name' => 'Stories', 'sort_order' => 1]);
        $board = $category->boards()->create(['name' => 'Chat', 'slug' => 'chat', 'is_ic' => false]);
        $author = User::factory()->create(['status' => 'approved']);

        return $board->threads()->create(['user_id' => $author->id, 'title' => 'A test thread', 'slug' => 'a-test-thread']);
    }
}
