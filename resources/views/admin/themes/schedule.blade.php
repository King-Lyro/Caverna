@php
    $scheduleKey = $schedule->id ?? 'new';
    $useOld = (string) old('_schedule', '') === (string) $scheduleKey;
    $value = fn ($key, $fallback = '') => $useOld ? old($key, $schedule->$key ?? $fallback) : ($schedule->$key ?? $fallback);
@endphp
<form method="POST" action="{{ $schedule->exists ? route('admin.themes.schedules.update', $schedule) : route('admin.themes.schedules.store') }}" class="cavernas-form" data-theme-schedule>
    @csrf @if ($schedule->exists) @method('PATCH') @endif
    <input type="hidden" name="_schedule" value="{{ $scheduleKey }}">
    <div class="form-columns">
        <label>Event name<input name="name" placeholder="e.g. Winter festival" value="{{ $value('name') }}" maxlength="120" required></label>
        <label>Theme to show<select name="site_theme_id" required>@foreach ($themes as $choice)<option value="{{ $choice->id }}" @selected((string) $value('site_theme_id') === (string) $choice->id)>{{ $choice->name }}</option>@endforeach</select></label>
        <label>How often?<select name="recurrence"><option value="once" @selected($value('recurrence') === 'once')>Just once</option><option value="annual" @selected($value('recurrence') === 'annual')>Every year</option></select></label>
        <label>Should this event run?<select name="is_enabled"><option value="1" @selected($value('is_enabled'))>Yes, switch on these dates</option><option value="0" @selected(! $value('is_enabled'))>Not yet, keep these dates saved</option></select></label>
    </div>
    <div class="form-columns" data-recurrence="once">
        <label>Start date<input type="date" name="starts_on" value="{{ $value('starts_on') }}" min="1900-01-01" max="9998-12-31"></label>
        <label>End date (inclusive)<input type="date" name="ends_on" value="{{ $value('ends_on') }}" min="1900-01-01" max="9998-12-31"></label>
    </div>
    <div class="form-columns" data-recurrence="annual">
        <label>First day (month-day)<input name="starts_md" placeholder="12-20" value="{{ $value('starts_md') }}" pattern="[0-9]{2}-[0-9]{2}" data-annual-date="First day"></label>
        <label>Last day (month-day)<input name="ends_md" placeholder="01-05" value="{{ $value('ends_md') }}" pattern="[0-9]{2}-[0-9]{2}" data-annual-date="Last day"></label>
    </div>
    <p class="theme-note">Both days are included, using {{ config('app.timezone') }}. Your selected active theme returns after the last day. Enabled events cannot overlap.</p>
    <button class="button button-primary" type="submit">{{ $schedule->exists ? 'Save event dates' : 'Add event dates' }}</button>
</form>
