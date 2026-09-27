<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Support\CavernasRules;
use Illuminate\View\View;

class AdminPopulationController extends Controller
{
    public function index(): View
    {
        $clans = ['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'];
        $counts = array_replace(array_fill_keys($clans, 0), CavernasRules::clanPopulationCounts());
        $roles = Character::query()->whereIn('allegiance', $clans)->whereIn('role', ['warrior', 'apprentice'])->whereIn('status', ['active', 'inactive'])->selectRaw('allegiance, role, count(*) as population')->groupBy('allegiance', 'role')->get()->groupBy('allegiance');

        return view('admin.population.index', ['clans' => $clans, 'counts' => $counts, 'roles' => $roles, 'lowest' => min($counts)]);
    }
}
