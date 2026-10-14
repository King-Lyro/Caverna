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
            'notifications' => $request->user()->notifications()->latest()->paginate(20)->through(function ($notification) {
                $data = $notification->data;
                if ($this->isBreedingRequest($data)) {
                    $data['title'] = preg_replace('/\b(?:mate|mating) requests?\b/i', 'Breeding request', data_get($data, 'title', 'Breeding request'));
                    $data['message'] = preg_replace('/\b(?:mate|mating) requests?\b/i', 'breeding request', data_get($data, 'message', 'Review your breeding request.'));
                    $data['url'] = route('dashboard').'#breeding-requests';
                    $notification->data = $data;
                }

                return $notification;
            }),
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
        if ($this->isBreedingRequest($data)) {
            return route('dashboard').'#breeding-requests';
        }

        if ($url = data_get($data, 'url')) {
            return $url;
        }

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

    private function isBreedingRequest(array $data): bool
    {
        return data_get($data, 'mate_request_id') || data_get($data, 'breeding_request_id')
            || preg_match('/\b(?:mate|mating|breeding) requests?\b/i', data_get($data, 'title', '')) === 1;
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
