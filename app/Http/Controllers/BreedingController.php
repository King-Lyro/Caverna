<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\BreedingRequest;
use App\Models\Litter;
use App\Models\LitterKit;
use App\Models\Pregnancy;
use App\Notifications\SiteNotification;
use App\Services\BreedingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use DomainException;

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
        abort_if($female->user_id === $male->user_id, 422, 'Members cannot breed their own characters.');
        abort_unless(in_array($request->user()->id, [$female->user_id, $male->user_id], true), 403);

        try {
            $breeding->validateRequest($female, $male, $request->user()->id);
        } catch (DomainException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()])->withInput();
        }

        abort_if(
            BreedingRequest::query()->where('female_character_id', $female->id)->where('male_character_id', $male->id)->where('status', 'pending')->exists(),
            422,
            'A breeding request is already waiting.',
        );
        $recipient = $female->user_id === $request->user()->id ? $male->user : $female->user;
        $breedingRequest = BreedingRequest::create([
            'female_character_id' => $female->id,
            'male_character_id' => $male->id,
            'requested_by' => $request->user()->id,
        ]);
        $recipient->notify(new SiteNotification([
            'title' => 'Breeding request',
            'message' => $request->user()->name.' asks to breed '.$female->name.' and '.$male->name.'. Review the request on your dashboard.',
            'url' => route('dashboard').'#breeding-requests',
            'breeding_request_id' => $breedingRequest->id,
        ]));

        return redirect()->route('dashboard')->with('status', 'Breeding request sent. Pregnancy will begin only if the other owner accepts.');
    }

    public function acceptRequest(BreedingRequest $breedingRequest, Request $request, BreedingService $breeding): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless(
            $breedingRequest->female->user_id === $request->user()->id || $breedingRequest->male->user_id === $request->user()->id,
            403,
        );
        abort_unless($breedingRequest->status === 'pending' && $breedingRequest->requested_by !== $request->user()->id, 422, 'This breeding request is no longer pending.');

        try {
            $pregnancy = DB::transaction(function () use ($breedingRequest, $request, $breeding) {
                $lockedRequest = BreedingRequest::query()->whereKey($breedingRequest->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedRequest->status === 'pending' && $lockedRequest->requested_by !== $request->user()->id, 422, 'This breeding request is no longer pending.');
                $female = Character::query()->whereKey($lockedRequest->female_character_id)->lockForUpdate()->firstOrFail();
                $male = Character::query()->whereKey($lockedRequest->male_character_id)->lockForUpdate()->firstOrFail();
                $breeding->validateRequest($female, $male, $lockedRequest->requested_by);
                $pregnancy = $breeding->beginPregnancy($female, $male, false, null, $lockedRequest->requested_by);
                $lockedRequest->update(['status' => 'accepted', 'accepted_at' => now()]);

                return $pregnancy;
            });
        } catch (DomainException $exception) {
            if (str_contains($exception->getMessage(), 'does not have enough crickets')) {
                $breedingRequest->requester->notify(new SiteNotification([
                    'title' => 'Breeding request needs funds',
                    'message' => 'Your breeding request was not processed because you do not have enough crickets to cover the role cost.',
                    'url' => route('dashboard').'#breeding-requests',
                    'breeding_request_id' => $breedingRequest->id,
                ]));
            }

            $message = str_contains($exception->getMessage(), 'does not have enough crickets')
                ? 'The sender lacks the required funds: '.$exception->getMessage()
                : $exception->getMessage();

            return back()->withErrors(['breeding' => $message]);
        }

        $breedingRequest->requester->notify(new SiteNotification([
            'title' => 'Breeding request accepted',
            'message' => 'Your breeding request was accepted. Pregnancy is due '.$pregnancy->due_at->format('M j, Y').'.',
            'character_id' => $breedingRequest->female_character_id,
            'url' => route('dashboard').'#breeding-requests',
        ]));

        return redirect()->route('dashboard')->with('status', 'Breeding request accepted. Pregnancy is due '.$pregnancy->due_at->format('M j, Y').'.');
    }

    public function rejectRequest(BreedingRequest $breedingRequest, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless(
            $breedingRequest->female->user_id === $request->user()->id || $breedingRequest->male->user_id === $request->user()->id,
            403,
        );
        abort_unless($breedingRequest->status === 'pending' && $breedingRequest->requested_by !== $request->user()->id, 422, 'This breeding request is no longer pending.');
        $breedingRequest->update(['status' => 'rejected']);
        $breedingRequest->requester->notify(new SiteNotification([
            'title' => 'Breeding request declined',
            'message' => 'Your breeding request was declined.',
            'url' => route('dashboard').'#breeding-requests',
            'breeding_request_id' => $breedingRequest->id,
        ]));

        return redirect()->route('dashboard')->with('status', 'Breeding request declined.');
    }

    public function birth(Pregnancy $pregnancy, Request $request, BreedingService $breeding): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($pregnancy->female->user_id === $request->user()->id || $request->user()->isStaff(), 403);
        try {
            $litter = $breeding->giveBirth($pregnancy);
        } catch (DomainException $exception) {
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
