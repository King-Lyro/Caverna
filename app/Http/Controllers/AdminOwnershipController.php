<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOwnershipController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $action = (string) $request->query('action', '');
        $events = AuditLog::with('actor')->where('auditable_type', Character::class)->whereIn('action', ['character.purchased', 'character.transferred'])
            ->when(in_array($action, ['character.purchased', 'character.transferred'], true), fn ($query) => $query->where('action', $action))
            ->when($search !== '', fn ($query) => $query->whereHas('actor', fn ($actor) => $actor->where('name', 'like', '%'.$search.'%')))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.ownership.index', compact('events', 'search', 'action'));
    }
}
