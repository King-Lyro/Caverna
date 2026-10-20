@extends('layouts.staff')

@section('content')
<div class="theme-workspace">
    <header class="theme-page-header">
        <div><h1>Themes & appearance</h1><p>Create and update site themes without touching the code.</p></div>
        <a class="button button-primary" href="{{ route('admin.themes.index') }}#create-theme">+ Create a theme</a>
    </header>
    @if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <nav class="theme-tabs" aria-label="Appearance settings">
        <a href="{{ route('admin.themes.index') }}" @if ($tab === 'themes') aria-current="page" @endif>Your themes</a>
        <a href="{{ route('admin.themes.index', ['tab' => 'schedules']) }}" @if ($tab === 'schedules') aria-current="page" @endif>Automatic switching</a>
        <a href="{{ route('admin.themes.index', ['tab' => 'defaults']) }}" @if ($tab === 'defaults') aria-current="page" @endif>Site-wide defaults</a>
    </nav>

    @if ($tab === 'themes')
        @php
            $liveKey = $appearance->schedule?->theme->key ?? ($appearance->stale ? 'fall' : $appearance->manual);
            $liveTheme = $themes->firstWhere('key', $liveKey);
        @endphp
        <section class="theme-live-panel">
            <div><span class="theme-badge theme-badge-live">On your site now</span><h2>{{ $liveTheme?->name ?? 'Fall' }}</h2>
                <p>@if ($appearance->schedule) {{ $appearance->schedule->name }} is running. Your selected active theme returns when this event ends. @else This is the theme visitors see. Edit it to update your site, or create another for a future event. @endif</p>
            </div>
            @if ($liveTheme)<a class="button button-primary" href="{{ route('admin.themes.edit', $liveTheme) }}">Edit current theme</a>@endif
        </section>
        <div class="theme-section-heading"><div><h2>Your theme collection</h2><p>Editing an unused theme will not change the live site.</p></div><span class="theme-count">{{ $themes->count() }} saved</span></div>
        <div class="theme-cards">
            @foreach ($themes as $theme)
                @php
                    $palette = array_replace(\App\Support\SitePresentation::palettes()['fall'], $theme->colors);
                    $paletteStyle = collect($palette)->filter(fn ($color) => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/D', $color))->map(fn ($color, $key) => '--sample-'.$key.':'.$color)->implode(';');
                    $artwork = $theme->presentation['header.image'] ?? null;
                @endphp
                <article class="theme-collection-card">
                    <div class="theme-card-sample" style="{{ $paletteStyle }}" aria-hidden="true">
                        <div class="theme-sample-banner">@if ($artwork)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($artwork) }}" alt="" loading="lazy">@else<span>Cavernas</span>@endif</div>
                        <div class="theme-sample-lines"><i></i><i></i><i></i></div><div class="theme-sample-boxes"><i></i><i></i><i></i></div>
                    </div>
                    <div class="theme-card-body">
                        <div class="theme-card-title"><h3>{{ $theme->name }}</h3>@if ($theme->key === $liveKey)<span class="theme-badge theme-badge-live">Live</span>@elseif ($theme->key === $appearance->manual)<span class="theme-badge">Active theme</span>@endif</div>
                        <p>{{ $theme->is_builtin ? 'Seasonal theme' : 'Your custom theme' }}@if ($theme->schedules->where('is_enabled', true)->isNotEmpty()) · Has scheduled dates @endif</p>
                        <div class="theme-card-actions">
                            <a class="button button-primary" href="{{ route('admin.themes.edit', $theme) }}">Customize</a>
                            <form method="POST" action="{{ route('admin.themes.preview', $theme) }}" target="_blank" rel="noopener">@csrf<button class="button theme-button-secondary" type="submit" title="Opens in a new tab">Preview</button></form>
                        </div>
                        @if ($theme->key !== $appearance->manual)<form class="theme-activate" method="POST" action="{{ route('theme.update', $theme->key) }}" data-confirm="Use {{ $theme->name }} as your active theme? Scheduled events will still run on their dates.">@csrf<button class="theme-link-button" type="submit">Use this theme</button></form>@endif
                        <details class="theme-more"><summary>More options</summary>
                            <form method="POST" action="{{ route('admin.themes.duplicate', $theme) }}">@csrf<button class="button" type="submit">Make a copy</button></form>
                            @unless ($theme->is_builtin)<form method="POST" action="{{ route('admin.themes.destroy', $theme) }}" data-confirm="Delete {{ $theme->name }}? This cannot be undone.">@csrf @method('DELETE')<button class="button theme-danger" type="submit">Delete theme</button></form>@endunless
                        </details>
                    </div>
                </article>
            @endforeach
        </div>
        <section id="create-theme" class="theme-panel theme-create-panel">
            <div class="theme-section-heading"><div><h2>Create a theme</h2><p>Pick a starting point, give it a name, then change only what you need.</p></div><span class="theme-badge">Not published automatically</span></div>
            <form method="POST" action="{{ route('admin.themes.store') }}" class="cavernas-form" data-theme-create>@csrf
                <div class="form-columns">
                    <label>1. Start with<select name="source_theme_id" required>@foreach ($themes as $source)<option value="{{ $source->id }}" @selected((string) old('source_theme_id', $themes->firstWhere('key', $appearance->manual)?->id) === (string) $source->id)>{{ $source->name }}</option>@endforeach</select><small>Copies its colors, artwork, and text. The original stays unchanged.</small></label>
                    <label>2. Name your theme<input name="name" maxlength="120" required placeholder="e.g. Winter festival" value="{{ old('name') }}"><small>Choose a name you will recognize later.</small></label>
                </div>
                <div class="theme-actions"><button class="button button-primary" type="submit">Create & start editing</button><span>You can preview it before anyone else sees it.</span></div>
            </form>
        </section>
    @elseif ($tab === 'schedules')
        <section class="theme-panel">
            <div class="theme-section-heading"><div><h2>Let your themes switch themselves</h2><p>Choose a theme and its dates. When the event ends, your selected active theme returns.</p></div><a class="button button-primary" href="#new-schedule">+ Add event dates</a></div>
            <div class="theme-schedule-status"><div><strong>{{ $appearance->scheduling ? 'Automatic switching is on' : 'Automatic switching is paused' }}</strong><p>Your selected active theme is {{ $themes->firstWhere('key', $appearance->manual)?->name ?? 'Fall' }}. Dates use {{ config('app.timezone') }}.</p></div>
                <form method="POST" action="{{ route('admin.themes.scheduling') }}">@csrf @method('PATCH')<input type="hidden" name="enabled" value="{{ $appearance->scheduling ? '0' : '1' }}"><button class="button theme-button-secondary" type="submit">{{ $appearance->scheduling ? 'Pause automatic switching' : 'Turn automatic switching on' }}</button></form>
            </div>
            @forelse ($schedules as $schedule)
                <details class="theme-schedule-card" @if ((string) old('_schedule') === (string) $schedule->id) open @endif>
                    <summary><span><strong>{{ $schedule->name }}</strong><small>{{ $schedule->theme->name }} · {{ $schedule->recurrence === 'annual' ? \Carbon\Carbon::parse('2000-'.$schedule->starts_md)->format('M j').' to '.\Carbon\Carbon::parse('2000-'.$schedule->ends_md)->format('M j').' every year' : \Carbon\Carbon::parse($schedule->starts_on)->format('M j, Y').' to '.\Carbon\Carbon::parse($schedule->ends_on)->format('M j, Y') }}</small></span><span class="theme-badge">{{ $schedule->is_enabled ? 'Enabled' : 'Disabled' }}</span><span class="theme-summary-action">Edit dates</span></summary>
                    @include('admin.themes.schedule', ['schedule' => $schedule])
                    <form method="POST" action="{{ route('admin.themes.schedules.destroy', $schedule) }}" data-confirm="Remove these event dates? The theme will not be deleted.">@csrf @method('DELETE')<button class="button theme-danger" type="submit">Remove event dates</button></form>
                </details>
            @empty
                <div class="theme-empty"><strong>No events scheduled yet</strong><p>Use your active theme all year, or add dates for holidays and site events below.</p></div>
            @endforelse
        </section>
        <section id="new-schedule" class="theme-panel"><h2>Add event dates</h2><p>The theme must already be saved. You can update it any time before the event.</p>
            @include('admin.themes.schedule', ['schedule' => new \App\Models\SiteThemeSchedule(['recurrence' => 'once', 'is_enabled' => true])])
        </section>
        <details class="theme-help"><summary>How do event dates work?</summary><p>Both the first and last day are included. Enabled events cannot overlap. Yearly events can span New Year; February 29 becomes February 28 in non-leap years. Pausing does not delete your dates. Selecting another active theme does not stop a scheduled event.</p></details>
    @else
        <section class="theme-panel"><h2>Content your themes share</h2><p>Use these settings for wording and artwork that should stay the same across themes. A theme's own changes take priority.</p><a class="button button-primary" href="{{ route('admin.appearance.edit') }}">Edit site-wide defaults</a><p class="theme-note">Changes here can appear on the live site immediately wherever a theme uses the default.</p></section>
        <section class="theme-panel"><h2>Looking for page content?</h2><p>Full articles and navigation cards have their own editors. Theme settings change their headings and surrounding artwork, not the articles themselves.</p>
            <div class="theme-editor-links">
                @foreach (['Guide' => route('admin.guide.edit'), 'Rules' => route('admin.rules.index'), 'Clans & outsiders' => route('admin.world.index'), 'Adoption page' => route('admin.adoption.content.edit'), 'Privacy' => route('admin.pages.edit', 'privacy'), 'Contact' => route('admin.pages.edit', 'contact'), 'Sidebar cards' => route('admin.content.modules')] as $label => $url)<a href="{{ $url }}">{{ $label }} <span aria-hidden="true">&rarr;</span></a>@endforeach
            </div>
        </section>
    @endif
</div>
@endsection
