@extends('layouts.staff')

@section('content')
@php
    $editorUrl = fn ($area) => $theme ? route('admin.themes.edit', [$theme, 'group' => $area]) : route('admin.appearance.edit', ['group' => $area]);
    $areaName = $group === 'colors' ? 'Name & colors' : $groups[$group];
    $liveKey = $appearance->schedule?->theme->key ?? ($appearance->stale ? 'fall' : $appearance->manual);
    $isLive = $theme && $theme->key === $liveKey;
@endphp
<div class="theme-workspace" data-theme-workspace>
    <a class="theme-back" href="{{ route('admin.themes.index') }}">&larr; All themes</a>
    <header class="theme-page-header">
        <div><p class="eyebrow">{{ $theme ? 'Customizing a theme' : 'Shared by your themes' }}</p><h1>{{ $theme?->name ?? 'Site-wide defaults' }}</h1><p>{{ $theme ? 'Choose a part of your site, make your changes, and save.' : 'Every theme uses these values unless you change that item inside the theme.' }}</p></div>
        @if ($theme)<form method="POST" action="{{ route('admin.themes.preview', $theme) }}" target="_blank" rel="noopener">@csrf<button class="button theme-button-secondary" type="submit" title="Opens in a new tab">Preview saved theme</button></form>@endif
    </header>
    @if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <div class="theme-notice">
        <strong>{{ ! $theme ? 'You are editing site-wide defaults.' : ($isLive ? 'This theme is live.' : 'You can prepare this theme privately.') }}</strong>
        <span>{{ ! $theme ? 'Saving updates every theme that uses these defaults.' : ($isLive ? 'Saving changes what visitors see. Make a copy first if you want to experiment.' : 'Saving will not switch the live site to this theme.') }}</span>
        @if ($isLive)<form method="POST" action="{{ route('admin.themes.duplicate', $theme) }}">@csrf<button class="theme-link-button" type="submit">Make a copy instead</button></form>@endif
    </div>
    <div class="theme-edit-layout">
        <nav class="theme-area-nav" aria-label="Choose what to change">
            <p>What would you like to change?</p>
            @if ($theme)<a href="{{ $editorUrl('colors') }}" @if ($group === 'colors') aria-current="page" @endif><strong>Name & colors</strong><small>The overall look of this theme</small></a>@endif
            @foreach (['header' => ['Header & season', 'Top image, tagline, event message'], 'home' => ['Homepage', 'Welcome banner, artwork, cards'], 'sidebars' => ['Sidebars', 'Left and right-hand site cards']] as $key => [$label, $description])
                <a href="{{ $editorUrl($key) }}" @if ($group === $key) aria-current="page" @endif><strong>{{ $label }}</strong><small>{{ $description }}</small></a>
            @endforeach
            <details @if (str_starts_with($group, 'page:')) open @endif>
                <summary>Individual pages <small>Page titles, banners & extra boxes</small></summary>
                <label class="theme-page-search" hidden data-page-search-label>Find a page<input type="search" placeholder="Search pages..." data-theme-page-search></label>
                <div class="theme-page-list">@foreach ($groups as $key => $label) @if (str_starts_with($key, 'page:'))<a data-theme-page-link href="{{ $editorUrl($key) }}" @if ($group === $key) aria-current="page" @endif>{{ $label }}</a>@endif @endforeach</div>
                <p hidden data-no-pages>No matching pages.</p>
            </details>
            <a class="theme-nav-help" href="{{ route('admin.themes.index', ['tab' => 'schedules']) }}"><strong>Set event dates</strong><small>Choose when themes switch</small></a>
        </nav>
        <div class="theme-edit-content">
            @if ($group === 'colors')
                @include('admin.themes.colors')
            @else
                <div class="theme-area-intro"><h2>{{ $areaName }}</h2><p>Open a section, then choose Custom from an item's dropdown to edit its text or images. Anything you leave alone stays as it is.</p></div>
                <form method="POST" enctype="multipart/form-data" action="{{ $theme ? route('admin.themes.presentation', $theme) : route('admin.appearance.update') }}" class="cavernas-form theme-editor" data-track-theme-changes>
                    @csrf @method('PATCH')<input type="hidden" name="group" value="{{ $group }}">
                    @foreach ($sections as $section)
                        @php
                            $sectionHasErrors = collect($section['keys'])->contains(fn ($fieldKey) => $errors->has('fields.'.array_search($fieldKey, array_keys($fields), true).'*'));
                            $extraSection = str_starts_with($section['title'], 'Extra');
                            $overrides = count(array_intersect($section['keys'], array_keys($values)));
                        @endphp
                        <details class="theme-editor-section" data-theme-section @if ($loop->first || $sectionHasErrors) open @endif>
                            <summary><span><strong>{{ $section['title'] }}</strong><small>{{ $section['description'] }}</small></span><span class="theme-summary-action">{{ $overrides ? $overrides.' changed' : ($extraSection ? 'Optional' : 'Edit') }}</span></summary>
                            <div class="theme-section-fields">
                                @foreach ($section['keys'] as $key)
                                    @include('admin.themes.field', ['field' => $fields[$key], 'index' => array_search($key, array_keys($fields), true)])
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                    <input type="hidden" name="form_complete" value="1">
                    @include('admin.themes.save-bar')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
