<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request, string $theme): RedirectResponse
    {
        abort_unless(array_key_exists($theme, trans('site.themes')), 404);
        $request->session()->put('theme', $theme);

        return back();
    }
}
