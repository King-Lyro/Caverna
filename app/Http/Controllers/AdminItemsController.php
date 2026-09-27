<?php

namespace App\Http\Controllers;

use App\Models\CharacterItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminItemsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $items = CharacterItem::with(['character', 'item', 'character.user'])
            ->when($search !== '', fn ($query) => $query->whereHas('character', fn ($character) => $character->where('name', 'like', '%'.$search.'%'))->orWhereHas('item', fn ($item) => $item->where('name', 'like', '%'.$search.'%')))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.items.index', compact('items', 'search'));
    }
}
