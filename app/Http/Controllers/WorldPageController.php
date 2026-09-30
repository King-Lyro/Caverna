<?php

namespace App\Http\Controllers;

use App\Models\WorldPage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorldPageController extends Controller
{
    public function show(string $kind, string $slug): View
    {
        $page = WorldPage::where('kind', $kind)->where('slug', $slug)->firstOrFail();

        return view('content.guide', [
            'page' => $page,
            'body' => Str::markdown($page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'indexUrl' => route('content.page', $kind),
            'indexLabel' => $kind === 'clans' ? 'All clans' : 'All outsiders',
        ]);
    }
}