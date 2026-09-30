<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Staff CP' }} | {{ __('site.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="staff-cp-body theme-{{ session('theme', 'spring') }}">
    <div class="staff-cp-shell">
        <header class="staff-cp-topbar">
            <a class="staff-cp-brand" href="{{ route('staff.index') }}"><strong>Staff CP</strong><span>{{ __('site.name') }}</span></a>
            <div class="staff-cp-topbar-actions">
                <a class="text-link" href="{{ route('home') }}">View site <span aria-hidden="true">↗</span></a>
                <form method="POST" action="{{ route('theme.update', session('theme', 'spring')) }}" class="theme-switcher">
                    @csrf
                    <label for="staff-theme-select">Theme</label>
                    <select id="staff-theme-select" name="theme" onchange="this.form.action='/theme/'+this.value; this.form.submit()">
                        @foreach (__('site.themes') as $themeKey => $themeLabel)<option value="{{ $themeKey }}" @selected(session('theme', 'spring') === $themeKey)>{{ $themeLabel }}</option>@endforeach
                    </select>
                </form>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="button" type="submit">Log out</button></form>
            </div>
        </header>

        <div class="staff-cp-layout">
            <aside class="staff-cp-sidebar" aria-label="Staff control panel navigation">
                <nav>
                    <p class="eyebrow">Staff</p>
                    <a @class(['is-active' => request()->routeIs('staff.index')]) href="{{ route('staff.index') }}">Overview</a>
                    <a @class(['is-active' => request()->routeIs('staff.applications*')]) href="{{ route('staff.applications') }}">Applications</a>
                    <a @class(['is-active' => request()->routeIs('staff.reports*')]) href="{{ route('staff.reports') }}">Reports</a>
                    <a @class(['is-active' => request()->routeIs('staff.litters*')]) href="{{ route('staff.litters') }}">Litter review</a>
                    @if (auth()->user()->isAdmin())
                        <p class="eyebrow staff-cp-nav-heading">Administration</p>
                        <a @class(['is-active' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}">Users & permissions</a>
                        <a @class(['is-active' => request()->routeIs('staff.characters*')]) href="{{ route('staff.characters') }}">Character records</a>
                        <a @class(['is-active' => request()->routeIs('admin.content.categories')]) href="{{ route('admin.content.categories') }}">Forum categories</a>
                        <a @class(['is-active' => request()->routeIs('admin.content.boards')]) href="{{ route('admin.content.boards') }}">Forum boards</a>
                        <a @class(['is-active' => request()->routeIs('admin.content.modules')]) href="{{ route('admin.content.modules') }}">Sidebar modules</a>
                        <a @class(['is-active' => request()->routeIs('admin.guide.*')]) href="{{ route('admin.guide.edit') }}">Guide editor</a>
                        <a @class(['is-active' => request()->routeIs('admin.pages.edit') && request()->route('slug') === 'privacy']) href="{{ route('admin.pages.edit', 'privacy') }}">Privacy page</a>
                        <a @class(['is-active' => request()->routeIs('admin.pages.edit') && request()->route('slug') === 'contact']) href="{{ route('admin.pages.edit', 'contact') }}">Contact page</a>
                        <a @class(['is-active' => request()->routeIs('admin.world.*')]) href="{{ route('admin.world.index') }}">World pages</a>
                        <a @class(['is-active' => request()->routeIs('admin.rules.*')]) href="{{ route('admin.rules.index') }}">Rules</a>
                        <a @class(['is-active' => request()->routeIs('admin.adoption.index')]) href="{{ route('admin.adoption.index') }}">Adoption oversight</a>
                        <a @class(['is-active' => request()->routeIs('admin.adoption.content.*')]) href="{{ route('admin.adoption.content.edit') }}">Adoption page</a>
                        <a @class(['is-active' => request()->routeIs('admin.population.*')]) href="{{ route('admin.population.index') }}">Population</a>
                        <a @class(['is-active' => request()->routeIs('admin.items.*')]) href="{{ route('admin.items.index') }}">Item audit</a>
                        <a @class(['is-active' => request()->routeIs('admin.lifecycle.*')]) href="{{ route('admin.lifecycle.index') }}">Lifecycle</a>
                        <a @class(['is-active' => request()->routeIs('admin.ownership.*')]) href="{{ route('admin.ownership.index') }}">Ownership</a>
                    @endif
                </nav>
            </aside>

            <main class="staff-cp-content">
                @if (isset($pageError) || session('error') || (isset($errors) && $errors->any()))
                    <div class="page-error" role="alert"><strong>Unable to complete your request.</strong><p>{{ $pageError ?? session('error') ?? (isset($errors) ? $errors->first() : '') }}</p></div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
