@if (auth()->user()?->isAdmin())
    @if ($appearance->preview)
    <div class="theme-status" role="status"><strong>Administrator preview: {{ $appearance->themes->firstWhere('key', $appearance->key)?->name }}. Visitors are unaffected.</strong><form method="POST" action="{{ route('admin.themes.preview.stop') }}" data-close-preview>@csrf<button class="button">Exit preview</button></form></div>
    @elseif ($appearance->schedule)
    <div class="theme-status" role="status">Scheduled theme: {{ $appearance->schedule->name }}. Selecting a base does not override it. <a href="{{ route('admin.themes.index') }}">Manage or pause schedules</a></div>
    @elseif (! $appearance->scheduling)
    <div class="theme-status" role="status">Theme scheduling is paused. <a href="{{ route('admin.themes.index') }}">Manage themes</a></div>
    @endif
    @if ($appearance->stale)<div class="form-alert" role="alert">The selected theme no longer exists. Fall is being used as the base fallback. <a href="{{ route('admin.themes.index') }}">Select a valid theme</a></div>@endif
@endif
