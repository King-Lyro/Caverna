<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'discord_username' => ['nullable', 'string', 'max:100'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'pronouns' => ['nullable', 'string', 'max:40'],
            'age' => ['nullable', 'integer', 'min:13', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'timezone'],
            'hide_personal_info' => ['nullable', 'boolean'],
            'hide_current_page' => ['nullable', 'boolean'],
            'is_absent' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($request->user()->avatar_path) {
                Storage::disk('public')->delete($request->user()->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }
        unset($validated['avatar']);
        foreach (['hide_personal_info', 'hide_current_page', 'is_absent'] as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $request->user()->update($validated);

        return back()->with('status', 'Your account settings were saved.');
    }
}
