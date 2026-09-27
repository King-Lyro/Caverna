<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Litter;
use App\Models\LitterKit;
use App\Models\Pregnancy;
use App\Services\BreedingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class BreedingController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureApproved($request);

        return view('breeding.create', ['characters' => Character::where('status', 'active')->with('user')->orderBy('name')->get()]);
    }

    public function store(Request $request, BreedingService $breeding): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'female_character_id' => ['required', 'integer', 'exists:characters,id'],
            'male_character_id' => ['required', 'integer', 'exists:characters,id'],
            'mates' => ['nullable', 'boolean'],
        ]);
        $female = Character::findOrFail($validated['female_character_id']);
        $male = Character::findOrFail($validated['male_character_id']);
        abort_unless($female->user_id === $request->user()->id || $male->user_id === $request->user()->id, 403);

        try {
            $pregnancy = $breeding->beginPregnancy($female, $male, (bool) ($validated['mates'] ?? false));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('characters.show', $female)->with('status', 'Pregnancy recorded. Due '.$pregnancy->due_at->format('M j, Y').'.');
    }

    public function birth(Pregnancy $pregnancy, Request $request, BreedingService $breeding): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($pregnancy->female->user_id === $request->user()->id || $request->user()->isStaff(), 403);
        try {
            $litter = $breeding->giveBirth($pregnancy);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['birth' => $exception->getMessage()]);
        }

        return back()->with('status', 'Birth recorded with '.$litter->surviving_count.' surviving kits.');
    }

    public function review(Request $request): View
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('staff.litters', ['litters' => Litter::with(['pregnancy.female', 'pregnancy.male', 'kits.owner'])->latest()->paginate(20)]);
    }

    public function updateKit(LitterKit $kit, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        $validated = $request->validate(['status' => ['required', 'in:surviving,deceased'], 'has_disability' => ['nullable', 'boolean']]);
        $kit->update(['status' => $validated['status'], 'has_disability' => (bool) ($validated['has_disability'] ?? false)]);

        return back()->with('status', 'Kit record updated.');
    }

    private function ensureApproved(Request $request): void
    {
        abort_unless($request->user()?->status === 'approved' || $request->user()?->isStaff(), 403);
    }
}
