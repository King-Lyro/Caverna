<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLifecycleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $filter = fn ($query) => $query->with('user')->when($search !== '', fn ($nested) => $nested->where(function ($searchQuery) use ($search) {
            $searchQuery->where('name', 'like', '%'.$search.'%')->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
        }));

        return view('admin.lifecycle.index', [
            'search' => $search,
            'frozen' => $filter(Character::where('is_frozen', true))->latest('updated_at')->paginate(15, ['*'], 'frozen')->withQueryString(),
            'inactive' => $filter(Character::where('status', 'inactive'))->latest('inactive_at')->paginate(15, ['*'], 'inactive')->withQueryString(),
            'archived' => $filter(Character::where('status', 'deceased'))->latest('died_at')->paginate(15, ['*'], 'archived')->withQueryString(),
        ]);
    }
}
