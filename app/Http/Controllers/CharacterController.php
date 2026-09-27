<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\ForumPost;
use App\Models\Inventory;
use App\Models\MateRequest;
use App\Support\CavernasRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $characters = Character::query()->with('user')->where('status', 'active')->when($search !== '', function ($query) use ($search) {
            $query->where(function ($nested) use ($search) {
                $nested->where('name', 'like', '%'.$search.'%')->orWhere('allegiance', 'like', '%'.$search.'%')->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
            });
        })->latest()->paginate(24)->withQueryString();

        return view('characters.index', compact('characters', 'search'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->ensureApproved($request);

        $hasOutsiderPass = Inventory::query()->where('user_id', $request->user()->id)
            ->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))
            ->where('quantity', '>', 0)->exists();

        return view('characters.create', compact('hasOutsiderPass'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male'],
            'eye_color' => ['nullable', 'string', 'max:80'],
            'age_moons' => ['required', 'numeric', 'min:6', 'max:240'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,Kittypet,Loner,Rogue'],
            'looks' => ['required', 'string', 'max:255'],
            'appearance' => ['required', 'string', 'min:250'],
            'personality' => ['required', 'string', 'min:250'],
            'history' => ['required', 'string', 'min:250'],
            'images' => ['required', 'array', 'size:3'],
            'forum_avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        if (str_word_count($validated['looks']) > 15) {
            throw ValidationException::withMessages(['looks' => 'Looks must be 15 words or fewer.']);
        }
        $validated['images'] = collect($request->file('images', []))->map(fn ($image) => $image->store('characters', 'public'))->all();
        $imageUrls = preg_split('/\r\n|\r|\n/', (string) $request->input('image_urls', ''));
        $imageUrls = array_values(array_filter($imageUrls));
        foreach (array_merge($request->input('images', []), $imageUrls) as $imageUrl) {
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                throw ValidationException::withMessages(['images' => 'Images must be valid URLs or uploaded image files.']);
            }
            $validated['images'][] = $imageUrl;
        }
        if (count($validated['images']) !== 3) {
            throw ValidationException::withMessages(['images' => 'Exactly three character images are required.']);
        }

        $character = DB::transaction(function () use ($request, $validated) {
            $ownedCount = Character::query()->where('user_id', $request->user()->id)->where('adopted', false)->count();
            $cost = $ownedCount >= 3 ? 200 : 0;
            $isOutsider = in_array($validated['allegiance'], ['Kittypet', 'Loner', 'Rogue'], true);
            abort_unless(CavernasRules::clanCreationAllowed($validated['allegiance']), 422, 'This Clan is currently at its population limit. Choose another Clan or wait for the balance to change.');
            $outsiderPass = $isOutsider ? Inventory::query()->where('user_id', $request->user()->id)->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->where('quantity', '>', 0)->lockForUpdate()->first() : null;
            abort_if($isOutsider && ! $outsiderPass, 422, 'An outsider access item is required for this allegiance.');
            $balance = (int) DB::table('cricket_ledger')->where('user_id', $request->user()->id)->sum('amount');
            abort_if($balance < $cost, 422, 'You need 200 crickets to create another character.');

            $character = Character::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'forum_avatar_path' => $request->file('forum_avatar')?->store('characters/avatars', 'public'),
                'eye_color' => $validated['eye_color'] ?? 'Unknown',
                'role' => CavernasRules::roleForAge((float) $validated['age_moons'], $validated['allegiance']),
                'energy' => 100,
                'status' => 'active',
            ]);
            if ($outsiderPass) {
                $outsiderPass->decrement('quantity');
            }
            if ($cost > 0) {
                DB::table('cricket_ledger')->insert(['user_id' => $request->user()->id, 'amount' => -$cost, 'type' => 'character_creation', 'description' => 'Character creation: '.$character->name, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $character;
        });

        return redirect()->route('characters.show', $character)->with('status', $character->name.' has entered Cavernas.');
    }

    public function show(Character $character): View
    {
        $character->load(['user', 'mates', 'mentors', 'apprentices', 'parentRelationships.relatedCharacter', 'appliedItems.item']);
        $characterPostQuery = ForumPost::query()->where('character_id', $character->id);
        $playerPostQuery = ForumPost::query()->where('user_id', $character->user_id);
        $recentThreads = ForumPost::query()->with('thread')->where('character_id', $character->id)->where('is_ic', true)->latest()->get()->unique('forum_thread_id')->take(5)->values();
        $parentIds = $character->parentRelationships->pluck('related_character_id');
        $familyKits = Character::query()->whereIn('id', function ($query) use ($character) {
            $query->select('character_id')->from('character_relationships')->where('related_character_id', $character->id)->where('type', 'parent')->where('status', 'accepted');
        })->where('id', '!=', $character->id)->orderBy('name')->get();
        $siblings = $parentIds->isEmpty() ? collect() : Character::query()->whereIn('id', function ($query) use ($parentIds) {
            $query->select('character_id')->from('character_relationships')->whereIn('related_character_id', $parentIds)->where('type', 'parent')->where('status', 'accepted');
        })->where('id', '!=', $character->id)->orderBy('name')->get();
        $ownershipHistory = AuditLog::query()->where('auditable_type', Character::class)->where('auditable_id', $character->id)->whereIn('action', ['character.purchased', 'character.transferred'])->with('actor')->latest()->take(5)->get();

        return view('characters.show', [
            'character' => $character,
            'ownedCharacters' => auth()->user()?->characters()->where('status', 'active')->where('id', '!=', $character->id)->orderBy('name')->get() ?? collect(),
            'profileStats' => [
                'characterPosts' => (clone $characterPostQuery)->where('is_ic', true)->count(),
                'characterThreads' => (clone $characterPostQuery)->where('is_ic', true)->distinct('forum_thread_id')->count('forum_thread_id'),
                'lastPostAt' => (clone $characterPostQuery)->where('is_ic', true)->latest()->value('created_at'),
                'playerPosts' => (clone $playerPostQuery)->count(),
                'playerThreads' => (clone $playerPostQuery)->distinct('forum_thread_id')->count('forum_thread_id'),
                'postCrickets' => (int) DB::table('cricket_ledger')->where('user_id', $character->user_id)->whereIn('type', ['post_thread', 'post_reply'])->sum('amount'),
                'playerCrickets' => (int) DB::table('cricket_ledger')->where('user_id', $character->user_id)->sum('amount'),
                'recentThreads' => $recentThreads,
                'familyKits' => $familyKits,
                'siblings' => $siblings,
                'ownershipHistory' => $ownershipHistory,
            ],
        ]);
    }

    public function requestMate(Character $character, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $from = Character::query()->whereKey($request->integer('from_character_id'))->where('user_id', $request->user()->id)->firstOrFail();
        abort_if($from->is($character), 422, 'A character cannot request its own mate.');
        abort_if($from->mates()->exists() || $character->mates()->exists() || $from->mate || $character->mate, 422, 'Both characters must be unmated.');
        abort_if($from->age_moons < 12 && $from->role === 'warrior', 422, 'Ordinary characters must be at least 12 moons old to take a mate.');
        abort_if($from->sex === 'female' && $from->energy < 50, 422, 'The female character needs 50 energy.');
        abort_if($from->sex === 'male' && $from->energy < 25, 422, 'The male character needs 25 energy.');
        abort_if(MateRequest::where(['from_character_id' => $from->id, 'to_character_id' => $character->id, 'status' => 'pending'])->exists(), 422, 'A request is already waiting.');

        DB::transaction(function () use ($from, $character, $request) {
            $cost = $this->mateCost($from);
            $balance = (int) DB::table('cricket_ledger')->where('user_id', $request->user()->id)->sum('amount');
            abort_if($balance < $cost, 422, 'You do not have enough crickets for this mate request.');
            if ($cost > 0) {
                DB::table('cricket_ledger')->insert(['user_id' => $request->user()->id, 'amount' => -$cost, 'type' => 'mate_request', 'description' => 'Mate request for '.$from->name, 'created_at' => now(), 'updated_at' => now()]);
            }
            MateRequest::create(['from_character_id' => $from->id, 'to_character_id' => $character->id]);
        });

        return back()->with('status', 'Mate request sent.');
    }

    public function acceptMate(MateRequest $mateRequest, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($mateRequest->toCharacter->user_id === $request->user()->id, 403);
        abort_unless($mateRequest->status === 'pending', 422, 'This request is no longer pending.');
        $mateRequest->update(['status' => 'accepted']);
        $from = $mateRequest->fromCharacter;
        $to = $mateRequest->toCharacter;
        $acceptedAt = now();
        $from->mates()->syncWithoutDetaching([$to->id => ['accepted_at' => $acceptedAt]]);
        $to->mates()->syncWithoutDetaching([$from->id => ['accepted_at' => $acceptedAt]]);
        $from->update(['mate' => $to->name]);
        $to->update(['mate' => $from->name]);

        return back()->with('status', 'The characters are now mates.');
    }

    public function requestMentor(Character $character, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $character = Character::query()->findOrFail($character->getKey());
        $apprentice = Character::whereKey($request->integer('apprentice_character_id'))->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($apprentice->role === 'apprentice', 422, 'Only apprentices can request a mentor.');
        $mentorRole = strtolower(trim((string) $character->getAttribute('role')));
        abort_unless(in_array($mentorRole, ['warrior', 'leader', 'deputy', 'medicine_cat'], true), 422, 'This character cannot mentor apprentices.');
        abort_if((string) $character->getAttribute('status') !== 'active' || (bool) $character->getAttribute('is_frozen'), 422, 'This mentor is unavailable.');
        abort_if(CharacterRelationship::where(['character_id' => $apprentice->id, 'related_character_id' => $character->id, 'type' => 'mentor', 'status' => 'pending'])->exists(), 422, 'A mentor request is already waiting.');

        CharacterRelationship::create(['character_id' => $apprentice->id, 'related_character_id' => $character->id, 'type' => 'mentor', 'status' => 'pending', 'requested_by' => $request->user()->id]);

        return back()->with('status', 'Mentor request sent.');
    }

    public function acceptMentor(CharacterRelationship $relationship, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($relationship->type === 'mentor' && $relationship->status === 'pending', 422, 'This mentor request is no longer active.');
        abort_unless($relationship->relatedCharacter->user_id === $request->user()->id, 403);
        $relationship->update(['status' => 'accepted', 'accepted_at' => now()]);

        return back()->with('status', 'Mentor relationship accepted.');
    }

    private function mateCost(Character $character): int
    {
        return match ($character->role) {
            'medicine_cat' => 50000,
            'leader' => 2000,
            'deputy' => 1000,
            default => 0,
        };
    }

    private function ensureApproved(Request $request): void
    {
        abort_unless($request->user()?->status === 'approved' || $request->user()?->isStaff(), 403);
    }
}
