<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(User $user): View
    {
        abort_unless($user->status === 'approved' || $user->isStaff() || auth()->user()?->isStaff(), 404);

        return view('members.show', [
            'member' => $user,
            'icPosts' => ForumPost::where('user_id', $user->id)->where('is_ic', true)->count(),
            'oocPosts' => ForumPost::where('user_id', $user->id)->where('is_ic', false)->count(),
            'characters' => Character::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'inactive'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
