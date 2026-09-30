<?php

namespace App\Http\Controllers;

use App\Models\WorldPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminWorldPageController extends Controller
{
    public function index(): View
    {
        return view('admin.world.index', [
            'pages' => WorldPage::orderBy('kind')->orderBy('sort_order')->orderBy('id')->get()->groupBy('kind'),
        ]);
    }

    public function create(Request $request): View
    {
        $kind = $request->query('kind', 'clans');
        abort_unless(in_array($kind, ['clans', 'outsiders'], true), 404);

        return view('admin.world.form', ['page' => new WorldPage(['kind' => $kind, 'sort_order' => 0])]);
    }

    public function edit(WorldPage $worldPage): View
    {
        return view('admin.world.form', ['page' => $worldPage]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kind = $request->input('kind');
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['clans', 'outsiders'])],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('world_pages')->where('kind', $kind)],
            ...$this->pageFields(),
        ]);

        $page = WorldPage::create($validated);

        return redirect()->route('admin.world.edit', $page)->with('status', 'Page created.');
    }

    public function update(Request $request, WorldPage $worldPage): RedirectResponse
    {
        $worldPage->update($request->validate($this->pageFields()));

        return back()->with('status', 'Page updated.');
    }

    public function destroy(WorldPage $worldPage): RedirectResponse
    {
        $worldPage->delete();

        return redirect()->route('admin.world.index')->with('status', 'Page deleted.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate(['image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120']]);
        $path = Storage::disk('public')->putFile('world', $validated['image']);

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    private function pageFields(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:1000'],
            'eyebrow' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:200000'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}