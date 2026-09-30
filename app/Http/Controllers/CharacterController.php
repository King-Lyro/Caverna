<?php

namespace App\Http\Controllers;

use App\Models\AdoptionListing;
use App\Models\AuditLog;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterRelationship;
use App\Models\CharacterSaleListing;
use App\Models\ForumPost;
use App\Models\Inventory;
use App\Models\MateRequest;
use App\Models\Pregnancy;
use App\Services\CharacterEnhancementService;
use App\Support\CavernasRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $characters = Character::query()->with('user')->whereIn('status', ['active', 'inactive'])->when($search !== '', function ($query) use ($search) {
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

        $clanAvailability = $this->clanAvailability();
        $availableEnhancements = Inventory::with('item')->where('user_id', $request->user()->id)->where('quantity', '>', 0)->get()
            ->filter(fn (Inventory $inventory) => CharacterEnhancementService::supports($inventory->item->effect) && $inventory->item->effect !== 'Outsider access');

        return view('characters.create', compact('hasOutsiderPass', 'clanAvailability', 'availableEnhancements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male'],
            'eye_color' => ['required', 'string', 'max:80'],
            'age_moons' => ['required', 'numeric', 'min:6', 'lt:161'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,Kittypet,Loner,Rogue'],
            'looks' => ['required', 'string', 'max:255'],
            'appearance' => ['required', 'string'],
            'personality' => ['required', 'string'],
            'history' => ['required', 'string'],
            'forum_avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'enhancements' => ['nullable', 'array'],
            'enhancements.*' => ['integer', 'distinct'],
            'disability' => ['nullable', 'string', 'max:255'],
        ]);
        $this->validateWriting($validated);
        [$imageUrls, $imageFiles] = $this->profileImages($request);

        $character = DB::transaction(function () use ($request, $validated, $imageUrls, $imageFiles) {
            $selectedIds = $validated['enhancements'] ?? [];
            $enhancements = Inventory::with('item')->where('user_id', $request->user()->id)->whereIn('id', $selectedIds)->lockForUpdate()->get();
            abort_unless($enhancements->count() === count($selectedIds), 422, 'Choose only items from your inventory.');
            $effects = $enhancements->pluck('item.effect')->all();
            abort_if(count($effects) !== count(array_unique($effects)), 422, 'Choose each enhancement type only once.');
            if (CharacterEnhancementService::requiresRareEyeItem($validated['eye_color']) && ! in_array('Rare eye color', $effects, true)) {
                throw ValidationException::withMessages(['eye_color' => 'Apply a rare eye color item to choose this eye color.']);
            }
            if (($validated['disability'] ?? null) && ! in_array('Disability', $effects, true)) {
                throw ValidationException::withMessages(['disability' => 'Apply a disability item to enter this detail.']);
            }
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
                'allegiance' => $isOutsider ? 'outsider' : $validated['allegiance'],
                'images' => [...$imageUrls, ...collect($imageFiles)->map(fn ($image) => $image->store('characters', 'public'))->all()],
                'user_id' => $request->user()->id,
                'forum_avatar_path' => $request->file('forum_avatar')?->store('characters/avatars', 'public'),
                'eye_color' => $validated['eye_color'] ?? 'Unknown',
                'role' => CavernasRules::roleForAge((float) $validated['age_moons'], $validated['allegiance']),
                'energy' => 100,
                'status' => 'active',
            ]);
            if ($outsiderPass) {
                $outsiderPass->decrement('quantity');
                CharacterItem::create(['character_id' => $character->id, 'shop_item_id' => $outsiderPass->shop_item_id, 'applied_by' => $request->user()->id, 'applied_at' => now()]);
            }
            foreach ($enhancements as $inventory) {
                abort_if($inventory->item->effect === 'Outsider access', 422, 'Outsider access is applied by choosing an outsider role.');
                app(CharacterEnhancementService::class)->apply($inventory, $character, $validated);
            }
            if ($cost > 0) {
                DB::table('cricket_ledger')->insert(['user_id' => $request->user()->id, 'amount' => -$cost, 'type' => 'character_creation', 'description' => 'Character creation: '.$character->name, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $character;
        });

        return redirect()->route('characters.show', $character)->with('status', $character->name.' has entered Cavernas.');
    }

    public function edit(Character $character, Request $request): View
    {
        $this->ensureApproved($request);
        abort_unless($character->user_id === $request->user()->id, 403);

        $hasOutsiderPass = Inventory::query()->where('user_id', $request->user()->id)
            ->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))
            ->where('quantity', '>', 0)->exists() || $character->appliedItems()->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->exists();

        $clanAvailability = $this->clanAvailability();

        $selectedAllegiance = $character->allegiance === 'outsider' && in_array($character->role, ['kittypet', 'loner', 'rogue'], true) ? ucfirst($character->role) : $character->allegiance;
        $availableEnhancements = Inventory::with('item')->where('user_id', $request->user()->id)->where('quantity', '>', 0)->get()
            ->filter(fn (Inventory $inventory) => CharacterEnhancementService::supports($inventory->item->effect) && $inventory->item->effect !== 'Outsider access'
                && ! CharacterItem::withTrashed()->where('character_id', $character->id)->where('shop_item_id', $inventory->shop_item_id)->exists());

        return view('characters.edit', compact('character', 'hasOutsiderPass', 'clanAvailability', 'selectedAllegiance', 'availableEnhancements'));
    }

    public function update(Character $character, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($character->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male'],
            'eye_color' => ['required', 'string', 'max:80'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,Kittypet,Loner,Rogue'],
            'looks' => ['required', 'string', 'max:255'],
            'appearance' => ['required', 'string'],
            'personality' => ['required', 'string'],
            'history' => ['required', 'string'],
            'forum_avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'enhancements' => ['nullable', 'array'],
            'enhancements.*' => ['integer', 'distinct'],
        ]);
        $this->validateWriting($validated);
        [$imageUrls, $imageFiles] = $this->profileImages($request, $character);
        if ($request->has('disability')) {
            $validated['disability'] = $request->validate(['disability' => ['required', 'string', 'max:255']])['disability'];
        }

        DB::transaction(function () use ($character, $request, $validated, $imageUrls, $imageFiles): void {
            $selectedIds = $validated['enhancements'] ?? [];
            $enhancements = Inventory::with('item')->where('user_id', $request->user()->id)->whereIn('id', $selectedIds)->lockForUpdate()->get();
            abort_unless($enhancements->count() === count($selectedIds), 422, 'Choose only items from your inventory.');
            $effects = $enhancements->pluck('item.effect')->all();
            abort_if(count($effects) !== count(array_unique($effects)) || in_array('Outsider access', $effects, true), 422, 'Choose each profile enhancement type only once.');
            if (CharacterEnhancementService::requiresRareEyeItem($validated['eye_color']) && ! in_array('Rare eye color', $effects, true) && ! $character->appliedItems()->whereHas('item', fn ($query) => $query->where('effect', 'Rare eye color'))->exists()) {
                throw ValidationException::withMessages(['eye_color' => 'Apply a rare eye color item to choose this eye color.']);
            }
            if (($validated['disability'] ?? null) && ! in_array('Disability', $effects, true) && ! $character->appliedItems()->whereHas('item', fn ($query) => $query->where('effect', 'Disability'))->exists()) {
                throw ValidationException::withMessages(['disability' => 'Apply a disability item before editing this field.']);
            }
            $currentSelection = $character->allegiance === 'outsider' && in_array($character->role, ['kittypet', 'loner', 'rogue'], true) ? ucfirst($character->role) : $character->allegiance;
            $allegianceChanged = $validated['allegiance'] !== $currentSelection;
            abort_if($allegianceChanged && ! CavernasRules::clanCreationAllowed($validated['allegiance']), 422, 'This Clan is currently at its population limit. Choose another Clan or wait for the balance to change.');
            $isOutsider = in_array($validated['allegiance'], ['Kittypet', 'Loner', 'Rogue'], true);
            $hadOutsiderRole = in_array($character->allegiance, ['outsider', 'Kittypet', 'Loner', 'Rogue'], true) || $character->appliedItems()->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->exists();
            abort_if(! $isOutsider && $hadOutsiderRole && $character->appliedItems()->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->exists(), 422, 'Remove the outsider access item before returning to a Clan.');
            $outsiderPass = $isOutsider && ! $hadOutsiderRole ? Inventory::query()->where('user_id', $request->user()->id)->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->where('quantity', '>', 0)->lockForUpdate()->first() : null;
            abort_if($isOutsider && ! $hadOutsiderRole && ! $outsiderPass, 422, 'An outsider access item is required for this allegiance.');
            abort_if($allegianceChanged && $character->role_locked, 422, 'Staff must change this character\'s assigned role or allegiance.');

            $character->update([
                ...$validated,
                'allegiance' => $isOutsider ? 'outsider' : $validated['allegiance'],
                'images' => [...$imageUrls, ...collect($imageFiles)->map(fn ($image) => $image->store('characters', 'public'))->all()],
                'forum_avatar_path' => $request->file('forum_avatar')?->store('characters/avatars', 'public') ?: $character->forum_avatar_path,
                'role' => $character->role_locked || $character->isNursingQueen()
                    ? $character->role : CavernasRules::roleForAge((float) $character->age_moons, $validated['allegiance']),
            ]);
            if ($outsiderPass) {
                $outsiderPass->decrement('quantity');
                CharacterItem::create(['character_id' => $character->id, 'shop_item_id' => $outsiderPass->shop_item_id, 'applied_by' => $request->user()->id, 'applied_at' => now()]);
            }
            foreach ($enhancements as $inventory) {
                app(CharacterEnhancementService::class)->apply($inventory, $character, $validated);
            }
        });

        return redirect()->route('characters.show', $character)->with('status', 'Character updated.');
    }

    public function removeItem(Character $character, CharacterItem $characterItem, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($character->user_id === $request->user()->id && $characterItem->character_id === $character->id, 403);
        $effect = $characterItem->item->effect;
        $updates = [];

        if ($effect === 'Outsider access') {
            $validated = $request->validate(['clan_allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan']]);
            abort_if($character->role_locked, 422, 'Staff must change this character\'s assigned role or allegiance.');
            abort_unless(CavernasRules::clanCreationAllowed($validated['clan_allegiance']), 422, 'This Clan is currently at its population limit. Choose another Clan.');
            $updates = ['allegiance' => $validated['clan_allegiance'], 'role' => CavernasRules::roleForAge((float) $character->age_moons, $validated['clan_allegiance'])];
        } elseif ($effect === 'Rare eye color') {
            $validated = $request->validate(['eye_color' => ['required', 'string', 'max:80']]);
            if (CharacterEnhancementService::requiresRareEyeItem($validated['eye_color'])) {
                throw ValidationException::withMessages(['eye_color' => 'Choose an ordinary eye color before removing this item.']);
            }
            $updates['eye_color'] = $validated['eye_color'];
        } elseif ($effect === 'Disability') {
            $updates['disability'] = null;
        } elseif (str_starts_with($effect, 'Rare trait:')) {
            $updates['traits'] = array_values(array_diff($character->traits ?? [], [trim(substr($effect, strlen('Rare trait:')))]));
        } elseif ($effect === 'Time freeze') {
            abort_unless($character->frozen_reason === 'item', 422, 'This character is currently frozen for another reason.');
            $updates = ['is_frozen' => false, 'frozen_reason' => null];
        } else {
            $flags = ['Male calico' => 'male_calico', 'Chimera/mosaicism' => 'chimera_mosaicism', 'Karpati/Roan/Salmiak' => 'karpati_roan_salmiak', 'White sepia' => 'white_sepia', 'Albino' => 'albino', 'Purebred' => 'purebred'];
            abort_unless(isset($flags[$effect]), 422, 'This item cannot be removed.');
            $updates[$flags[$effect]] = false;
            $updates['traits'] = array_values(array_diff($character->traits ?? [], [$effect]));
        }

        DB::transaction(function () use ($character, $characterItem, $updates): void {
            $character->update($updates);
            $characterItem->delete();
        });

        return back()->with('status', 'Item removed from character. It has not returned to inventory.');
    }

    private function validateWriting(array $validated): void
    {
        if (str_word_count($validated['looks']) > 15) {
            throw ValidationException::withMessages(['looks' => 'Looks must be 15 words or fewer.']);
        }
        foreach (['appearance', 'personality', 'history'] as $field) {
            if (str_word_count($validated[$field]) < 250) {
                throw ValidationException::withMessages([$field => ucfirst($field).' must be at least 250 words.']);
            }
        }
    }

    private function clanAvailability(): array
    {
        return collect(['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'])
            ->mapWithKeys(fn (string $clan) => [$clan => CavernasRules::clanCreationAllowed($clan)])->all();
    }

    private function profileImages(Request $request, ?Character $character = null): array
    {
        $files = array_values(array_filter($request->file('images', [])));
        Validator::make(['images' => $files], ['images' => ['array', 'max:3'], 'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']])->validate();
        $lines = preg_split('/\r\n|\r|\n/', (string) $request->input('image_urls', ''));
        $urls = array_values(array_filter(array_map('trim', array_merge($request->input('images', []), $lines))));
        foreach ($urls as $url) {
            if (! in_array($url, $character?->images ?? [], true) && (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) {
                throw ValidationException::withMessages(['images' => 'Images must be valid web URLs or existing character images.']);
            }
        }
        if (count($urls) + count($files) !== 3) {
            throw ValidationException::withMessages(['images' => 'Exactly three character images are required.']);
        }

        return [$urls, $files];
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
        $eligibleApprentices = auth()->user()?->characters()->where('role', 'apprentice')->where('status', 'active')->where('is_frozen', false)->orderBy('name')->get() ?? collect();

        return view('characters.show', [
            'character' => $character,
            'ownedCharacters' => auth()->user()?->characters()->where('status', 'active')->where('is_frozen', false)->where('age_moons', '>=', 12)->where('id', '!=', $character->id)->orderBy('name')->get() ?? collect(),
            'eligibleApprentices' => $eligibleApprentices,
            'saleListing' => CharacterSaleListing::where('character_id', $character->id)->where('status', 'available')->first(),
            'adoptionListing' => AdoptionListing::where('character_id', $character->id)->where('status', 'available')->first(),
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
        abort_if((float) $from->age_moons < 12 || (float) $character->age_moons < 12, 422, 'Both characters must be at least 12 moons old to take a mate.');
        abort_if($from->status !== 'active' || $character->status !== 'active' || $from->is_frozen || $character->is_frozen, 422, 'Only active, unfrozen characters may take a mate.');
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
        abort_if($mateRequest->fromCharacter->status !== 'active' || $mateRequest->toCharacter->status !== 'active' || $mateRequest->fromCharacter->is_frozen || $mateRequest->toCharacter->is_frozen, 422, 'Only active, unfrozen characters may take a mate.');
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
        abort_if($apprentice->status !== 'active' || $apprentice->is_frozen, 422, 'Only active, unfrozen apprentices may request a mentor.');
        $mentorRole = strtolower(trim((string) $character->getAttribute('role')));
        abort_unless(in_array($mentorRole, ['warrior', 'leader', 'deputy'], true), 422, 'This character cannot mentor apprentices. Medicine cat apprentices can only be assigned by staff.');
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
        abort_if($relationship->character->status !== 'active' || $relationship->character->is_frozen || $relationship->relatedCharacter->status !== 'active' || $relationship->relatedCharacter->is_frozen, 422, 'Both mentor and apprentice must be active and unfrozen.');
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
