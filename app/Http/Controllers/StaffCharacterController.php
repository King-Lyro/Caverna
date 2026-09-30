<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Character;
use App\Models\CharacterRelationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffCharacterController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('staff.characters', ['characters' => Character::with('user')->latest()->paginate(20)]);
    }

    public function edit(Request $request, Character $character): View
    {
        $this->ensureAdmin($request);

        $medicineCats = $character->role === 'medicine_cat_apprentice'
            ? Character::where('role', 'medicine_cat')->where('allegiance', $character->allegiance)->where('status', 'active')->where('is_frozen', false)->orderBy('name')->get()
            : collect();

        return view('staff.character-edit', compact('character', 'medicineCats'));
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sex' => ['sometimes', 'in:female,male'],
            'age_moons' => ['sometimes', 'numeric', 'min:0', 'max:240'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,outsider,Kittypet,Loner,Rogue'],
            'role' => ['required', 'in:kit,apprentice,warrior,queen,elder,leader,deputy,medicine_cat,medicine_cat_apprentice,kittypet,loner,rogue'],
            'eye_color' => ['nullable', 'string', 'max:80'],
            'looks' => ['sometimes', 'string', 'max:255'],
            'appearance' => ['sometimes', 'string'],
            'personality' => ['sometimes', 'string'],
            'history' => ['sometimes', 'string'],
            'disability' => ['nullable', 'string', 'max:255'],
            'image_urls' => ['nullable', 'string'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'forum_avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'role_locked' => ['nullable', 'boolean'],
            'is_frozen' => ['nullable', 'boolean'],
            'frozen_reason' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,inactive,deceased'],
            'energy' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        if ($request->filled('image_urls') || $request->hasFile('images')) {
            $urls = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $request->input('image_urls', '')))));
            foreach ($urls as $url) {
                if (! in_array($url, $character->images ?? [], true) && (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) {
                    throw ValidationException::withMessages(['images' => 'Images must be valid web URLs or existing character images.']);
                }
            }
            $files = $request->file('images', []);
            if (count($urls) + count($files) !== 3) {
                throw ValidationException::withMessages(['images' => 'Exactly three character images are required.']);
            }
            $validated['images'] = [...$urls, ...collect($files)->map(fn ($image) => $image->store('characters', 'public'))->all()];
        }
        if ($request->hasFile('forum_avatar')) {
            $validated['forum_avatar_path'] = $request->file('forum_avatar')->store('characters/avatars', 'public');
        }
        unset($validated['image_urls'], $validated['forum_avatar']);
        $before = $character->only(array_keys($validated));
        $deceased = $validated['status'] === 'deceased' || ($validated['age_moons'] ?? $character->age_moons) >= 161;
        $reservedRole = in_array($validated['role'], ['leader', 'deputy', 'medicine_cat', 'medicine_cat_apprentice', 'queen'], true);
        $character->update([...$validated, 'status' => $deceased ? 'deceased' : $validated['status'], 'energy' => $deceased ? 0 : $validated['energy'], 'role_locked' => $reservedRole || (bool) ($validated['role_locked'] ?? false), 'is_frozen' => (bool) ($validated['is_frozen'] ?? false), 'frozen_reason' => $validated['is_frozen'] ?? false ? ($validated['frozen_reason'] ?? 'staff') : null, 'died_at' => $deceased ? ($character->died_at ?: now()) : $character->died_at, 'archived_at' => $deceased ? ($character->archived_at ?: now()) : $character->archived_at]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'character.updated', 'auditable_type' => Character::class, 'auditable_id' => $character->id, 'changes' => ['before' => $before, 'after' => $character->only(array_keys($validated))], 'ip_address' => $request->ip()]);

        return redirect()->route('staff.characters.edit', $character)->with('status', 'Character updated and change logged.');
    }

    public function assignMedicineMentor(Request $request, Character $character): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate(['mentor_id' => ['required', 'integer', 'exists:characters,id']]);
        $mentor = Character::findOrFail($validated['mentor_id']);
        abort_unless($character->role === 'medicine_cat_apprentice' && $character->status === 'active' && ! $character->is_frozen, 422, 'Choose an active medicine cat apprentice.');
        abort_unless($mentor->role === 'medicine_cat' && $mentor->allegiance === $character->allegiance && $mentor->status === 'active' && ! $mentor->is_frozen, 422, 'Choose an active medicine cat from the same Clan.');

        DB::transaction(function () use ($character, $mentor, $request): void {
            CharacterRelationship::where('character_id', $character->id)->where('type', 'mentor')->delete();
            CharacterRelationship::create(['character_id' => $character->id, 'related_character_id' => $mentor->id, 'type' => 'mentor', 'status' => 'accepted', 'requested_by' => $request->user()->id, 'accepted_at' => now()]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'character.mentor_assigned', 'auditable_type' => Character::class, 'auditable_id' => $character->id, 'changes' => ['mentor_id' => $mentor->id], 'ip_address' => $request->ip()]);
        });

        return back()->with('status', 'Medicine cat mentor assigned.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }
}
