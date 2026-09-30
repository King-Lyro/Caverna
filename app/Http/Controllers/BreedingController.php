<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Litter;
use App\Models\LitterKit;
use App\Models\Pregnancy;
use App\Services\BreedingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class BreedingController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureApproved($request);

        $characters = Character::where('status', 'active')->with('user')->orderBy('name')->get();

        return view('breeding.create', ['characters' => $characters, 'breedingCosts' => $characters->mapWithKeys(fn (Character $character) => [$character->id => app(BreedingService::class)->breedingCostFor($character)])]);
    }

    public function store(Request $request, BreedingService $breeding): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate([
            'female_character_id' => ['required', 'integer', 'exists:characters,id'],
            'male_character_id' => ['required', 'integer', 'exists:characters,id'],
        ]);
        $female = Character::findOrFail($validated['female_character_id']);
        $male = Character::findOrFail($validated['male_character_id']);
        abort_unless($female->user_id === $request->user()->id || $male->user_id === $request->user()->id, 403);

        try {
            $pregnancy = $breeding->beginPregnancy($female, $male, false, null, $request->user()->id);
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

    public function bulkUpdateKits(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        $validated = $request->validate([
            'kits' => ['required', 'array'],
            'kits.*.status' => ['required', 'in:surviving,deceased'],
            'kits.*.has_disability' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($validated): void {
            foreach ($validated['kits'] as $id => $fields) {
                LitterKit::findOrFail($id)->update($fields);
            }
        });

        return back()->with('status', 'Litter records updated.');
    }

    private function ensureApproved(Request $request): void
    {
        abort_unless($request->user()?->status === 'approved' || $request->user()?->isStaff(), 403);
    }
}
