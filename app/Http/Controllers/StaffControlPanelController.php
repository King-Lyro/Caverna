<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ModerationReport;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffControlPanelController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('staff.index', [
            'pendingApplications' => User::where('status', 'pending')->count(),
            'openReports' => ModerationReport::where('status', 'open')->count(),
            'activePregnancies' => Pregnancy::where('status', 'pregnant')->count(),
            'activeCharacters' => Character::whereIn('status', ['active', 'inactive'])->count(),
        ]);
    }
}
