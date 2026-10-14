<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ThemeController extends Controller
{
    public function update(Request $request, string $theme): RedirectResponse
    {
        abort_unless(array_key_exists($theme, trans('site.themes')), 404);
        abort_unless($request->user()?->isAdmin(), 403);
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'theme'],
            ['value' => $theme, 'updated_at' => now(), 'created_at' => now()],
        );
        Cache::forget('site_theme');

        return back()->with('status', 'The site theme was updated.');
    }
}
