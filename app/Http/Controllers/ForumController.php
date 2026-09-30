<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForumController extends Controller
{
    public function index(): View
    {
        return view('forum.index', [
            'categories' => ForumCategory::query()
                ->with(['boards' => fn ($query) => $query->withCount('threads')])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function board(ForumBoard $board): View
    {
        return view('forum.board', [
            'board' => $board->load('category'),
            'threads' => $board->threads()->with('author')->withCount('posts')->latest('is_pinned')->latest()->paginate(20),
        ]);
    }

    public function thread(ForumThread $thread): View
    {
        return view('forum.thread', [
            'thread' => $thread->load(['board.category', 'author']),
            'posts' => $thread->posts()->with(['author', 'character'])->oldest()->paginate(20),
            'characters' => auth()->user() ? Character::where('user_id', auth()->id())->whereIn('status', ['active', 'inactive'])->orderBy('name')->get() : collect(),
            'boards' => auth()->user()?->isStaff() ? ForumBoard::with('category')->orderBy('name')->get() : collect(),
        ]);
    }

    public function createThread(ForumBoard $board, Request $request): View|RedirectResponse
    {
        if (! $this->isApproved($request)) {
            return redirect()->route('login');
        }

        return view('forum.create-thread', ['board' => $board, 'characters' => Character::where('user_id', $request->user()->id)->whereIn('status', ['active', 'inactive'])->orderBy('name')->get()]);
    }

    public function storeThread(ForumBoard $board, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'body' => $this->postBodyRules($board->is_ic),
            'character_id' => [$board->is_ic ? 'required' : 'nullable', 'integer', 'exists:characters,id'],
        ]);
        $character = $board->is_ic ? $this->eligibleCharacter($request, $validated['character_id'], $board->slug) : null;

        $thread = DB::transaction(function () use ($board, $request, $validated, $character) {
            $thread = $board->threads()->create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(6)),
            ]);
            $thread->posts()->create([
                'user_id' => $request->user()->id,
                'character_id' => $character?->id,
                'body' => $validated['body'],
                'is_ic' => $board->is_ic,
            ]);
            $this->applyTerritoryEnergy($character, $board->slug);
            $this->awardCrickets($request->user()->id, $board->is_ic ? 20 : 0, 'thread', 'New thread: '.$thread->title, $thread->id);

            return $thread;
        });

        return redirect()->route('forum.thread', $thread);
    }

    public function storeReply(ForumThread $thread, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_if($thread->is_locked, 403, 'This thread is locked.');
        $validated = $request->validate([
            'body' => $this->postBodyRules($thread->board->is_ic),
            'character_id' => [$thread->board->is_ic ? 'required' : 'nullable', 'integer', 'exists:characters,id'],
        ]);
        $character = $thread->board->is_ic ? $this->eligibleCharacter($request, $validated['character_id'], $thread->board->slug) : null;

        DB::transaction(function () use ($thread, $request, $validated, $character) {
            $post = $thread->posts()->create([
                'user_id' => $request->user()->id,
                'character_id' => $character?->id,
                'body' => $validated['body'],
                'is_ic' => $thread->board->is_ic,
            ]);
            $this->applyTerritoryEnergy($character, $thread->board->slug);
            $this->awardCrickets($request->user()->id, $thread->board->is_ic ? 10 : 0, 'reply', 'Reply in: '.$thread->title, $post->id);
        });

        return back()->with('status', 'Your reply has been added to the story.');
    }

    public function updatePost(ForumPost $post, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($post->user_id === $request->user()->id || $request->user()->isStaff(), 403);
        $validated = $request->validate(['body' => $this->postBodyRules($post->is_ic)]);
        $post->update(['body' => $validated['body'], 'edited_at' => now()]);

        return back()->with('status', 'Post updated.');
    }

    private function isApproved(Request $request): bool
    {
        return $request->user()?->isStaff() || $request->user()?->status === 'approved';
    }

    private function postBodyRules(bool $isIc): array
    {
        if (! $isIc) {
            return ['nullable', 'string', 'min:1'];
        }

        return ['required', 'string', function (string $attribute, mixed $value, $fail): void {
            if (str_word_count((string) $value) < 70) {
                $fail('In-character posts must be at least 70 words.');
            }
        }];
    }

    private function ensureApproved(Request $request): void
    {
        abort_unless($this->isApproved($request), 403, 'Your account must be approved before posting.');
    }

    private function awardCrickets(int $userId, int $amount, string $type, string $description, int $referenceId): void
    {
        if ($amount === 0) {
            return;
        }

        DB::table('cricket_ledger')->insert([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => 'post_'.$type,
            'description' => $description,
            'reference_type' => ForumThread::class,
            'reference_id' => $referenceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function eligibleCharacter(Request $request, int $characterId, string $boardSlug): Character
    {
        $character = Character::query()->whereKey($characterId)->where('user_id', $request->user()->id)->firstOrFail();
        $home = str_replace('-territory', '', $boardSlug);
        $allegiance = strtolower(str_replace('clan', '', $character->allegiance));
        $isHome = str_contains($home, $allegiance);
        abort_unless($character->status === 'active' || ($character->status === 'inactive' && $isHome), 422, 'Only living characters may post, and inactive characters must post at home.');
        abort_if($character->is_frozen, 422, 'Frozen characters cannot post.');
        abort_if($character->energy <= 0 && ! $isHome, 422, 'This character cannot enter enemy territory with zero energy.');

        return $character;
    }

    private function applyTerritoryEnergy(?Character $character, string $boardSlug): void
    {
        if (! $character) {
            return;
        }
        $home = str_replace('-territory', '', $boardSlug);
        $allegiance = strtolower(str_replace('clan', '', $character->allegiance));
        $isHome = str_contains($home, $allegiance);
        $energy = max(0, min(100, $character->energy + ($isHome ? 20 : -10)));
        $character->forceFill(['energy' => $energy, 'last_ic_post_at' => now(), 'status' => $energy > 0 ? 'active' : 'inactive', 'inactive_at' => $energy > 0 ? null : now()])->save();
    }
}
