<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\MateRequest;
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

        return view('characters.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male'],
            'age_moons' => ['required', 'numeric', 'min:6', 'max:240'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,Kittypet,Loner,Rogue'],
            'looks' => ['required', 'string', 'max:255'],
            'appearance' => ['required', 'string', 'min:250'],
            'personality' => ['required', 'string', 'min:250'],
            'history' => ['required', 'string', 'min:250'],
            'images' => ['required', 'array', 'size:3'],
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
            $outsiderPass = $isOutsider ? Inventory::query()->where('user_id', $request->user()->id)->whereHas('item', fn ($query) => $query->where('effect', 'Outsider access'))->where('quantity', '>', 0)->lockForUpdate()->first() : null;
            abort_if($isOutsider && ! $outsiderPass, 422, 'An outsider access item is required for this allegiance.');
            $balance = (int) DB::table('cricket_ledger')->where('user_id', $request->user()->id)->sum('amount');
            abort_if($balance < $cost, 422, 'You need 200 crickets to create another character.');

            $character = Character::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'role' => 'warrior',
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
        return view('characters.show', [
            'character' => $character,
            'ownedCharacters' => auth()->user()?->characters()->where('status', 'active')->where('id', '!=', $character->id)->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function requestMate(Character $character, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $from = Character::query()->whereKey($request->integer('from_character_id'))->where('user_id', $request->user()->id)->firstOrFail();
        abort_if($from->is($character), 422, 'A character cannot request its own mate.');
        abort_if($from->mate || $character->mate, 422, 'Both characters must be unmated.');
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
        $mateRequest->fromCharacter->update(['mate' => $mateRequest->toCharacter->name]);
        $mateRequest->toCharacter->update(['mate' => $mateRequest->fromCharacter->name]);

        return back()->with('status', 'The characters are now mates.');
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
