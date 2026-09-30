<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminStaticPageController extends Controller
{
    public function edit(string $slug): View
    {
        return view('admin.pages.edit', ['page' => ContentPage::where('slug', $slug)->firstOrFail(), 'slug' => $slug]);
    }

    public function update(string $slug, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'eyebrow' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:200000'],
        ]);

        ContentPage::where('slug', $slug)->firstOrFail()->update($validated);

        return back()->with('status', ucfirst($slug).' page updated.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate(['image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120']]);
        $path = Storage::disk('public')->putFile('pages', $validated['image']);

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }
}
