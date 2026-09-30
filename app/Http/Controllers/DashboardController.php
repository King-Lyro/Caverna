<?php

namespace App\Http\Controllers;

use App\Models\AdoptionApplication;
use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\CharacterSaleListing;
use App\Models\CharacterTransfer;
use App\Models\Inventory;
use App\Models\MateRequest;
use App\Models\Pregnancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->isStaff() || $user->status === 'approved', 403);

        return view('dashboard', [
            'characters' => Character::where('user_id', $user->id)->where('status', '!=', 'deceased')->latest()->get(),
            'crickets' => (int) DB::table('cricket_ledger')->where('user_id', $user->id)->sum('amount'),
            'inventoryCount' => Inventory::where('user_id', $user->id)->where('quantity', '>', 0)->sum('quantity'),
            'pendingMateRequests' => MateRequest::whereHas('toCharacter', fn ($query) => $query->where('user_id', $user->id))->where('status', 'pending')->count(),
            'mentorRequests' => CharacterRelationship::where('type', 'mentor')->where('status', 'pending')->whereHas('relatedCharacter', fn ($query) => $query->where('user_id', $user->id))->with(['character', 'relatedCharacter'])->latest()->get(),
            'pregnancies' => Pregnancy::whereHas('female', fn ($query) => $query->where('user_id', $user->id))->where('status', 'pregnant')->with(['female', 'male'])->latest()->get(),
            'transferRequests' => CharacterTransfer::where('to_user_id', $user->id)->where('status', 'pending')->with(['character', 'sender'])->latest()->get(),
            'transferHistory' => CharacterTransfer::where(fn ($query) => $query->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id))->whereIn('status', ['accepted', 'rejected'])->with(['character', 'sender', 'recipient'])->latest()->limit(10)->get(),
            'saleHistory' => CharacterSaleListing::where(fn ($query) => $query->where('owner_id', $user->id)->orWhere('buyer_id', $user->id))->whereIn('status', ['sold', 'withdrawn'])->with(['character', 'owner', 'buyer'])->latest()->limit(10)->get(),
            'adoptionApplications' => AdoptionApplication::where('user_id', $user->id)->with('listing')->latest()->limit(10)->get(),
        ]);
    }
}
