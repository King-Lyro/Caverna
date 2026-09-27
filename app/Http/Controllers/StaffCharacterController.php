<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('staff.character-edit', compact('character'));
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'allegiance' => ['required', 'in:ThunderClan,RiverClan,ShadowClan,WindClan,outsider,Kittypet,Loner,Rogue'],
            'role' => ['required', 'in:kit,apprentice,warrior,queen,elder,leader,deputy,medicine_cat,medicine_cat_apprentice,kittypet,loner,rogue'],
            'eye_color' => ['nullable', 'string', 'max:80'],
            'role_locked' => ['nullable', 'boolean'],
            'is_frozen' => ['nullable', 'boolean'],
            'frozen_reason' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,inactive,deceased'],
            'energy' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $before = $character->only(array_keys($validated));
        $character->update([...$validated, 'role_locked' => (bool) ($validated['role_locked'] ?? false), 'is_frozen' => (bool) ($validated['is_frozen'] ?? false), 'frozen_reason' => $validated['is_frozen'] ?? false ? ($validated['frozen_reason'] ?? 'staff') : null, 'died_at' => $validated['status'] === 'deceased' ? ($character->died_at ?: now()) : $character->died_at, 'archived_at' => $validated['status'] === 'deceased' ? ($character->archived_at ?: now()) : $character->archived_at]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'character.updated', 'auditable_type' => Character::class, 'auditable_id' => $character->id, 'changes' => ['before' => $before, 'after' => $character->only(array_keys($validated))], 'ip_address' => $request->ip()]);

        return redirect()->route('staff.characters.edit', $character)->with('status', 'Character updated and change logged.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }
}
