@if (auth()->user()?->isAdmin())
<form method="POST" action="{{ route('theme.update', $appearance->manual) }}" class="theme-switcher">
    @csrf
    <label for="{{ $id }}">Active theme</label>
    <select id="{{ $id }}" name="theme">
        @foreach ($appearance->themes as $option)<option value="{{ $option->key }}" @selected($appearance->manual === $option->key)>{{ $option->name }}</option>@endforeach
    </select>
    <button class="button" type="submit">Apply</button>
</form>
@endif
