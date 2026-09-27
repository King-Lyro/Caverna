<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $cutoff = now()->subDays(30);

        return view('activity.index', [
            'cutoff' => $cutoff,
            'characters' => Character::query()
                ->with('user')
                ->where('status', 'active')
                ->where(function ($query) use ($cutoff) {
                    $query->whereNull('last_ic_post_at')->orWhere('last_ic_post_at', '<', $cutoff);
                })
                ->orderBy('last_ic_post_at')
                ->paginate(30),
        ]);
    }
}
