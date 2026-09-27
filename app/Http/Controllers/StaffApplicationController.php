<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffApplicationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('staff.applications', [
            'applications' => User::query()->where('status', 'pending')->latest()->paginate(20),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);

        $user->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
        ])->save();

        return back()->with('status', $user->name.' has been approved.');
    }
}
