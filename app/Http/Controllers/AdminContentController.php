<?php

namespace App\Http\Controllers;

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\SidebarModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function categories(): View
    {
        return view('admin.content.categories', [
            'categories' => ForumCategory::with('boards')->orderBy('sort_order')->get(),
        ]);
    }

    public function boards(): View
    {
        return view('admin.content.boards', [
            'categories' => ForumCategory::with('boards')->orderBy('sort_order')->get(),
        ]);
    }

    public function modules(): View
    {
        return view('admin.content.modules', [
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

    public function bulkUpdateCategories(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.name' => ['required', 'string', 'max:120'],
            'categories.*.description' => ['nullable', 'string', 'max:1000'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        DB::transaction(function () use ($validated): void {
            foreach ($validated['categories'] as $id => $fields) {
                ForumCategory::findOrFail($id)->update($fields);
            }
        });

        return back()->with('status', 'Forum categories updated.');
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

    public function bulkUpdateBoards(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'boards' => ['required', 'array'],
            'boards.*.forum_category_id' => ['required', 'exists:forum_categories,id'],
            'boards.*.name' => ['required', 'string', 'max:120'],
            'boards.*.description' => ['nullable', 'string', 'max:1000'],
            'boards.*.is_ic' => ['required', 'boolean'],
            'boards.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        DB::transaction(function () use ($validated): void {
            foreach ($validated['boards'] as $id => $fields) {
                ForumBoard::findOrFail($id)->update($fields);
            }
        });

        return back()->with('status', 'Forum boards updated.');
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

    public function bulkUpdateModules(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.title' => ['required', 'string', 'max:120'],
            'modules.*.body' => ['required', 'string', 'max:2000'],
            'modules.*.link_text' => ['nullable', 'string', 'max:80'],
            'modules.*.link_url' => ['nullable', 'string', 'max:255'],
            'modules.*.placement' => ['required', 'in:left,right'],
            'modules.*.sort_order' => ['required', 'integer', 'min:0'],
            'modules.*.is_enabled' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($validated): void {
            foreach ($validated['modules'] as $id => $fields) {
                SidebarModule::findOrFail($id)->update($fields);
            }
        });

        return back()->with('status', 'Sidebar modules updated.');
    }
}
