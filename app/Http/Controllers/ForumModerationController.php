<?php

namespace App\Http\Controllers;

use App\Models\ForumBoard;
use App\Models\ForumPost;
use App\Models\ForumThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumModerationController extends Controller
{
    public function lockThread(ForumThread $thread, Request $request): RedirectResponse
    {
        $this->ensureStaff($request);
        $thread->update(['is_locked' => ! $thread->is_locked]);

        return back()->with('status', $thread->is_locked ? 'Thread locked.' : 'Thread unlocked.');
    }

    public function stickyThread(ForumThread $thread, Request $request): RedirectResponse
    {
        $this->ensureStaff($request);
        $thread->update(['is_pinned' => ! $thread->is_pinned]);

        return back()->with('status', $thread->is_pinned ? 'Thread pinned.' : 'Thread unpinned.');
    }

    public function moveThread(ForumThread $thread, Request $request): RedirectResponse
    {
        $this->ensureStaff($request);
        $validated = $request->validate(['forum_board_id' => ['required', 'exists:forum_boards,id']]);
        $thread->update(['forum_board_id' => $validated['forum_board_id']]);

        return redirect()->route('forum.thread', $thread)->with('status', 'Thread moved to '.ForumBoard::findOrFail($validated['forum_board_id'])->name.'.');
    }

    public function destroyThread(ForumThread $thread, Request $request): RedirectResponse
    {
        $this->ensureStaff($request);
        $board = $thread->board;
        $thread->delete();

        return redirect()->route('forum.board', $board)->with('status', 'Thread deleted.');
    }

    public function destroyPost(ForumPost $post, Request $request): RedirectResponse
    {
        $this->ensureStaff($request);
        abort_if($post->thread->posts()->count() <= 1, 422, 'Delete the thread instead of its only post.');
        $post->delete();

        return back()->with('status', 'Post deleted.');
    }

    private function ensureStaff(Request $request): void
    {
        abort_unless($request->user()?->isStaff(), 403);
    }
}
