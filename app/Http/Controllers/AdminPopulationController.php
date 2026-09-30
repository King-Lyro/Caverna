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
        $population = Character::query()->whereIn('allegiance', [...$clans, 'outsider', 'Kittypet', 'Loner', 'Rogue'])->whereIn('status', ['active', 'inactive'])
            ->selectRaw('allegiance, sex, role, count(*) as population')->groupBy('allegiance', 'sex', 'role')->get();
        $clanStats = $population->whereIn('allegiance', $clans)->whereIn('role', ['warrior', 'apprentice'])->groupBy('allegiance');
        $outsiderStats = $population->whereIn('allegiance', ['outsider', 'Kittypet', 'Loner', 'Rogue']);
        $creationAllowed = collect($clans)->mapWithKeys(fn (string $clan) => [$clan => CavernasRules::clanCreationAllowed($clan)]);

        return view('admin.population.index', compact('clans', 'counts', 'clanStats', 'outsiderStats', 'creationAllowed') + ['lowest' => min($counts)]);
    }
}
