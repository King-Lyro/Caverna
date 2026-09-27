<?php

namespace App\Http\Controllers;

use App\Models\AdoptionListing;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $destination = $this->destination($item->data);

        return redirect($destination ?: route('notifications'));
    }

    private function destination(array $data): ?string
    {
        if ($characterId = data_get($data, 'character_id')) {
            $character = Character::find($characterId);
            if ($character) {
                return route('characters.show', $character);
            }
        }

        if ($listingId = data_get($data, 'listing_id')) {
            $listing = AdoptionListing::find($listingId);
            if ($listing) {
                return route('adoption.show', $listing);
            }
        }

        return null;
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
