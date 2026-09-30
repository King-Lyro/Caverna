<?php

namespace App\Http\Controllers;

use App\Models\AdoptionApplication;
use App\Models\AdoptionListing;
use App\Models\AuditLog;
use App\Models\Character;
use App\Models\CharacterSaleListing;
use App\Models\CharacterTransfer;
use App\Models\ContentPage;
use App\Models\User;
use App\Notifications\AdoptionNotification;
use App\Notifications\CharacterTransferNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdoptionController extends Controller
{
    public function index(): View
    {
        $page = ContentPage::where('slug', 'adoption')->firstOrFail();

        return view('adoption.index', [
            'page' => $page,
            'body' => Str::markdown($page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'listings' => AdoptionListing::query()->where('status', 'available')->with(['character', 'owner'])->latest()->paginate(12),
        ]);
    }

    public function sales(): View
    {
        return view('sales.index', ['listings' => CharacterSaleListing::with(['character', 'owner'])->where('status', 'available')->latest()->paginate(12)]);
    }

    public function createSale(Request $request): View
    {
        $this->ensureApproved($request);

        return view('sales.create', ['characters' => Character::where('user_id', $request->user()->id)->where('status', 'active')->where('is_frozen', false)->where('adopted', false)->orderBy('name')->get()]);
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate(['character_id' => ['required', 'exists:characters,id'], 'price' => ['required', 'integer', 'min:1', 'max:1000000']]);
        $character = Character::where('user_id', $request->user()->id)->where('status', 'active')->where('is_frozen', false)->findOrFail($validated['character_id']);
        CharacterSaleListing::create(['character_id' => $character->id, 'owner_id' => $request->user()->id, 'price' => $validated['price'], 'status' => 'available']);
        $character->update(['is_frozen' => true, 'frozen_reason' => 'sale']);

        return redirect()->route('sales.index')->with('status', $character->name.' is now listed for sale.');
    }

    public function withdrawSale(CharacterSaleListing $listing, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($listing->owner_id === $request->user()->id || $request->user()->isStaff(), 403);
        abort_unless($listing->status === 'available', 422, 'This sale listing is no longer available.');
        DB::transaction(function () use ($listing): void {
            $listing->character->update(['is_frozen' => false, 'frozen_reason' => null]);
            $listing->update(['status' => 'withdrawn']);
        });

        return back()->with('status', 'The sale listing was withdrawn.');
    }

    public function purchaseSale(CharacterSaleListing $listing, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_if($listing->owner_id === $request->user()->id, 422, 'You cannot purchase your own character.');
        DB::transaction(function () use ($listing, $request): void {
            $locked = CharacterSaleListing::whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'available', 422, 'This character is no longer for sale.');
            $balance = (int) DB::table('cricket_ledger')->where('user_id', $request->user()->id)->lockForUpdate()->sum('amount');
            abort_if($balance < $locked->price, 422, 'You do not have enough crickets for this character.');
            DB::table('cricket_ledger')->insert(['user_id' => $request->user()->id, 'amount' => -$locked->price, 'type' => 'character_purchase', 'description' => 'Purchased '.$locked->character->name, 'reference_type' => CharacterSaleListing::class, 'reference_id' => $locked->id, 'created_at' => now(), 'updated_at' => now()]);
            $locked->character->update(['user_id' => $request->user()->id, 'is_frozen' => false, 'frozen_reason' => null]);
            $locked->update(['status' => 'sold', 'buyer_id' => $request->user()->id, 'sold_at' => now()]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'character.purchased', 'auditable_type' => Character::class, 'auditable_id' => $locked->character_id, 'changes' => ['price' => $locked->price, 'seller_id' => $locked->owner_id], 'ip_address' => $request->ip()]);
            $locked->owner->notify(new CharacterTransferNotification($locked->character, 'Your character was purchased:'));
        });

        return redirect()->route('characters.show', $listing->character)->with('status', 'The character joined your camp.');
    }

    public function requestTransfer(Character $character, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate(['recipient_id' => ['nullable', 'integer', 'exists:users,id'], 'recipient_email' => ['nullable', 'email']]);
        abort_unless($character->user_id === $request->user()->id, 403);
        $recipient = $validated['recipient_id'] ?? null ? User::find($validated['recipient_id']) : User::where('email', $validated['recipient_email'] ?? null)->first();
        abort_unless($recipient, 422, 'No member was found with that email.');
        abort_if($recipient->id === $request->user()->id, 422, 'You cannot transfer a character to yourself.');
        abort_if($character->status !== 'active' || $character->is_frozen, 422, 'Only active, unfrozen characters can be transferred.');

        CharacterTransfer::create(['character_id' => $character->id, 'from_user_id' => $request->user()->id, 'to_user_id' => $recipient->id]);
        $recipient->notify(new CharacterTransferNotification($character, 'You have a transfer request for'));

        return back()->with('status', 'Transfer request sent to '.$recipient->name.'.');
    }

    public function acceptTransfer(CharacterTransfer $transfer, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($transfer->to_user_id === $request->user()->id, 403);
        abort_unless($transfer->status === 'pending', 422, 'This transfer request is no longer active.');
        DB::transaction(function () use ($transfer, $request): void {
            $locked = CharacterTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $character = $locked->character()->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending' && $character->user_id === $locked->from_user_id && ! $character->is_frozen, 422, 'This character is no longer transferable.');
            $character->update(['user_id' => $request->user()->id]);
            $locked->update(['status' => 'accepted', 'accepted_at' => now()]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'character.transferred', 'auditable_type' => Character::class, 'auditable_id' => $character->id, 'changes' => ['from_user_id' => $locked->from_user_id, 'to_user_id' => $locked->to_user_id], 'ip_address' => $request->ip()]);
            $locked->sender->notify(new CharacterTransferNotification($character, 'Your transfer of'));
        });

        return redirect()->route('characters.show', $transfer->character)->with('status', 'The character joined your camp.');
    }

    public function create(Request $request): View
    {
        $this->ensureApproved($request);

        return view('adoption.create', ['characters' => Character::where('user_id', $request->user()->id)->where('status', 'active')->where('adopted', false)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $validated = $request->validate($this->listingRules());
        $character = Character::where('user_id', $request->user()->id)->where('status', 'active')->where('adopted', false)->findOrFail($validated['character_id']);
        AdoptionListing::create([...$validated, 'owner_id' => $request->user()->id, 'status' => 'available', 'claim_policy' => $validated['claim_policy'] ?? 'application']);
        $character->update(['is_frozen' => true, 'frozen_reason' => 'adoption']);

        return redirect()->route('adoption.index')->with('status', $character->name.' is now available for adoption.');
    }

    public function show(AdoptionListing $listing): View
    {
        abort_unless($listing->status === 'available', 404);

        return view('adoption.show', ['listing' => $listing->load(['character', 'owner'])]);
    }

    public function withdraw(AdoptionListing $listing, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($listing->owner_id === $request->user()->id || $request->user()->isStaff(), 403);
        abort_unless($listing->status === 'available', 422, 'This listing is no longer available.');
        DB::transaction(function () use ($listing): void {
            $listing->character?->update(['is_frozen' => false, 'frozen_reason' => null]);
            $listing->update(['status' => 'withdrawn']);
        });

        return back()->with('status', 'The adoption listing was withdrawn.');
    }

    public function claim(AdoptionListing $listing, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($listing->claim_policy === 'instant', 422, 'This listing requires an application.');
        $this->claimListing($listing, $request);

        return redirect()->route('characters.show', $listing->character)->with('status', 'The character has joined your camp.');
    }

    public function apply(AdoptionListing $listing, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        abort_unless($listing->claim_policy === 'application', 422, 'This listing is available for instant claim.');
        $validated = $request->validate(['message' => ['required', 'string', 'min:30', 'max:3000']]);
        abort_if($listing->owner_id === $request->user()->id, 422, 'You cannot apply for your own listing.');
        abort_if($listing->applications()->where('user_id', $request->user()->id)->where('status', 'pending')->exists(), 422, 'You already have an application waiting.');
        AdoptionApplication::create(['adoption_listing_id' => $listing->id, 'user_id' => $request->user()->id, 'message' => $validated['message']]);
        $listing->owner?->notify(new AdoptionNotification($listing, $request->user()->name.' submitted an application for '.$listing->title.'.'));

        return back()->with('status', 'Your adoption application has been sent.');
    }

    public function review(AdoptionApplication $application, Request $request): RedirectResponse
    {
        $this->ensureApproved($request);
        $application->load('listing');
        abort_unless($request->user()->isStaff() || $application->listing->owner_id === $request->user()->id, 403);
        $validated = $request->validate(['status' => ['required', 'in:approved,rejected'], 'reviewer_notes' => ['nullable', 'string', 'max:3000']]);

        DB::transaction(function () use ($application, $request, $validated): void {
            $lockedApplication = AdoptionApplication::with('listing')->whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedApplication->status === 'pending' && $lockedApplication->listing->status === 'available', 422, 'This application is no longer active.');
            if ($validated['status'] === 'approved') {
                $listing = AdoptionListing::whereKey($lockedApplication->adoption_listing_id)->lockForUpdate()->firstOrFail();
                $character = $listing->character()->lockForUpdate()->firstOrFail();
                $character->update(['user_id' => $lockedApplication->user_id, 'adopted' => true, 'is_frozen' => false, 'frozen_reason' => null]);
                $listing->update(['status' => 'claimed', 'claimed_by' => $lockedApplication->user_id, 'claimed_at' => now()]);
                $listing->applications()->whereKeyNot($lockedApplication->id)->where('status', 'pending')->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
                $lockedApplication->applicant->notify(new AdoptionNotification($listing, 'Your application for '.$listing->title.' was approved.'));
            } elseif ($validated['status'] === 'rejected') {
                $lockedApplication->applicant->notify(new AdoptionNotification($lockedApplication->listing, 'Your application for '.$lockedApplication->listing->title.' was rejected.'));
            }
            $lockedApplication->update([...$validated, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });

        return back()->with('status', 'Adoption application updated.');
    }

    private function claimListing(AdoptionListing $listing, Request $request): void
    {
        DB::transaction(function () use ($listing, $request): void {
            $locked = AdoptionListing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'available' && $locked->character_id, 422, 'This adoption listing is no longer available.');
            $character = $locked->character()->lockForUpdate()->firstOrFail();
            $character->update(['user_id' => $request->user()->id, 'adopted' => true, 'is_frozen' => false, 'frozen_reason' => null]);
            $locked->update(['status' => 'claimed', 'claimed_by' => $request->user()->id, 'claimed_at' => now()]);
        });
    }

    private function ensureApproved(Request $request): void
    {
        abort_unless($request->user()?->status === 'approved' || $request->user()?->isStaff(), 403);
    }

    private function listingRules(): array
    {
        return ['character_id' => ['required', 'integer', 'exists:characters,id'], 'title' => ['required', 'string', 'max:120'], 'description' => ['required', 'string', 'max:3000'], 'claim_policy' => ['required', 'in:application,instant'], 'eligibility' => ['nullable', 'string', 'max:2000'], 'birthplace' => ['nullable', 'string', 'max:120'], 'parents' => ['nullable', 'string', 'max:255'], 'size_build' => ['nullable', 'string', 'max:255'], 'coloration' => ['nullable', 'string', 'max:1000'], 'eyes' => ['nullable', 'string', 'max:255'], 'siblings' => ['nullable', 'string', 'max:1000'], 'spirit_symbol' => ['nullable', 'string', 'max:255'], 'appearance' => ['nullable', 'string', 'max:5000'], 'personality' => ['nullable', 'string', 'max:5000'], 'history' => ['nullable', 'string', 'max:10000'], 'adopter_notes' => ['nullable', 'string', 'max:3000'], 'contact_instructions' => ['nullable', 'string', 'max:2000']];
    }
}
