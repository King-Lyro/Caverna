<?php

namespace App\Http\Controllers;

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\SidebarModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function index(): View
    {
        return view('admin.content.index', [
            'categories' => ForumCategory::with('boards')->orderBy('sort_order')->get(),
            'modules' => SidebarModule::orderBy('placement')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'sort_order' => ['required', 'integer', 'min:0']]);
        ForumCategory::create($validated);

        return back()->with('status', 'Forum category created.');
    }

    public function updateCategory(Request $request, ForumCategory $category): RedirectResponse
    {
        $category->update($request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'sort_order' => ['required', 'integer', 'min:0']]));

        return back()->with('status', 'Forum category updated.');
    }

    public function storeBoard(Request $request): RedirectResponse
    {
        $validated = $request->validate(['forum_category_id' => ['required', 'exists:forum_categories,id'], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'is_ic' => ['nullable', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']]);
        ForumBoard::create([...$validated, 'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(6)), 'is_ic' => (bool) ($validated['is_ic'] ?? false)]);

        return back()->with('status', 'Forum board created.');
    }

    public function updateBoard(Request $request, ForumBoard $board): RedirectResponse
    {
        $validated = $request->validate(['forum_category_id' => ['required', 'exists:forum_categories,id'], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'is_ic' => ['nullable', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']]);
        $board->update([...$validated, 'is_ic' => (bool) ($validated['is_ic'] ?? false)]);

        return back()->with('status', 'Forum board updated.');
    }

    public function storeModule(Request $request): RedirectResponse
    {
        SidebarModule::create($request->validate(['title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:2000'], 'link_text' => ['nullable', 'string', 'max:80'], 'link_url' => ['nullable', 'string', 'max:255'], 'placement' => ['required', 'in:left,right'], 'sort_order' => ['required', 'integer', 'min:0'], 'is_enabled' => ['nullable', 'boolean']]) + ['is_enabled' => (bool) $request->boolean('is_enabled')]);

        return back()->with('status', 'Sidebar module created.');
    }

    public function updateModule(Request $request, SidebarModule $module): RedirectResponse
    {
        $module->update($request->validate(['title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:2000'], 'link_text' => ['nullable', 'string', 'max:80'], 'link_url' => ['nullable', 'string', 'max:255'], 'placement' => ['required', 'in:left,right'], 'sort_order' => ['required', 'integer', 'min:0'], 'is_enabled' => ['nullable', 'boolean']]) + ['is_enabled' => (bool) $request->boolean('is_enabled')]);

        return back()->with('status', 'Sidebar module updated.');
    }
}
