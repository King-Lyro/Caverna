<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request, string $theme): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $selected = $request->validate(['theme' => ['sometimes', 'required', 'string', 'max:120']])['theme'] ?? $theme;

        return app(AdminThemeController::class)->select($request, $selected);
    }
}
