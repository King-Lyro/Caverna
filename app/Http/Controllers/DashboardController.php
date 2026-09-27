<?php

namespace App\Http\Controllers;

use App\Models\Character;
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
            'pregnancies' => Pregnancy::whereHas('female', fn ($query) => $query->where('user_id', $user->id))->where('status', 'pregnant')->with(['female', 'male'])->latest()->get(),
        ]);
    }
}
