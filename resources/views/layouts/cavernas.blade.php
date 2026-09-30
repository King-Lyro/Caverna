<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('site.name') }} | {{ __('site.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cavernas-body theme-{{ session('theme', 'spring') }}">
    <div class="site-frame">
        <header class="site-header">
            <a class="brand" href="{{ url('/') }}" aria-label="{{ __('site.name') }} home">
                <span>
                    <strong>{{ __('site.name') }}</strong>
                    <small>{{ __('site.tagline') }}</small>
                </span>
            </a>
            <div class="header-status">{{ __('site.season') }}</div>
        </header>

        <nav class="site-nav" aria-label="Primary navigation">
            <a @class(['is-active' => request()->routeIs('home')]) href="{{ route('home') }}">{{ __('site.nav.home') }}</a>
            <a @class(['is-active' => request()->routeIs('forum', 'forum.*')]) href="{{ route('forum') }}">{{ __('site.nav.forum') }}</a>
            <details class="nav-menu">
                <summary @class(['is-active' => request()->routeIs('content.page') && in_array(request()->route('page'), ['rules', 'guide', 'clans', 'map', 'outsiders'], true)])>{{ __('site.nav.explore') }}</summary>
                <div class="nav-menu-panel">
                    <a @class(['is-active' => request()->routeIs('content.page') && request()->route('page') === 'rules']) href="{{ route('content.page', 'rules') }}">{{ __('site.nav.rules') }}</a>
                    <a @class(['is-active' => request()->routeIs('content.page') && request()->route('page') === 'guide']) href="{{ route('content.page', 'guide') }}">{{ __('site.nav.guide') }}</a>
                    <a @class(['is-active' => request()->routeIs('content.page') && request()->route('page') === 'clans']) href="{{ route('content.page', 'clans') }}">{{ __('site.nav.clans') }}</a>
                    <a @class(['is-active' => request()->routeIs('content.page') && request()->route('page') === 'map']) href="{{ route('content.page', 'map') }}">{{ __('site.nav.map') }}</a>
                    <a @class(['is-active' => request()->routeIs('content.page') && request()->route('page') === 'outsiders']) href="{{ route('content.page', 'outsiders') }}">{{ __('site.nav.outsiders') }}</a>
                </div>
            </details>
            <details class="nav-menu">
                <summary @class(['is-active' => request()->routeIs('characters*', 'activity', 'breeding*')])>{{ __('site.nav.characters') }}</summary>
                <div class="nav-menu-panel">
                    <a @class(['is-active' => request()->routeIs('characters', 'characters.show', 'characters.create')]) href="{{ route('characters') }}">{{ __('site.nav.characters') }}</a>
                    <a @class(['is-active' => request()->routeIs('characters.memorial')]) href="{{ route('characters.memorial') }}">{{ __('site.nav.memorial') }}</a>
                    <a @class(['is-active' => request()->routeIs('activity')]) href="{{ route('activity') }}">{{ __('site.nav.activity') }}</a>
                    @auth
                        @if (auth()->user()->status === 'approved' || auth()->user()->isStaff())<a @class(['is-active' => request()->routeIs('breeding*')]) href="{{ route('breeding.create') }}">{{ __('site.nav.breeding') }}</a>@endif
                    @endauth
                </div>
            </details>
            <details class="nav-menu">
                <summary @class(['is-active' => request()->routeIs('shop*', 'inventory', 'adoption*', 'sales*', 'crickets')])>{{ __('site.nav.market') }}</summary>
                <div class="nav-menu-panel">
                    <a @class(['is-active' => request()->routeIs('shop*')]) href="{{ route('shop') }}">{{ __('site.nav.shop') }}</a>
                    @auth
                        <a @class(['is-active' => request()->routeIs('inventory')]) href="{{ route('inventory') }}">{{ __('site.nav.inventory') }}</a>
                        <a @class(['is-active' => request()->routeIs('crickets')]) href="{{ route('crickets') }}">{{ __('site.nav.crickets') }}</a>
                    @endauth
                    <a @class(['is-active' => request()->routeIs('adoption*')]) href="{{ route('adoption.index') }}">{{ __('site.nav.adoption') }}</a>
                    <a @class(['is-active' => request()->routeIs('sales*')]) href="{{ route('sales.index') }}">{{ __('site.nav.sales') }}</a>
                </div>
            </details>
            <span class="nav-spacer"></span>
            @auth
                @if (auth()->user()->isStaff())
                    <a @class(['staff-cp-link', 'is-active' => request()->routeIs('staff.*', 'admin.*')]) href="{{ route('staff.index') }}">Staff CP</a>
                @endif
                <details class="nav-menu nav-menu-end nav-account">
                    <summary @class(['is-active' => request()->routeIs('dashboard', 'account.*', 'notifications*')])><span class="nav-user-name">{{ auth()->user()->name }}</span>@if (auth()->user()->unreadNotifications->isNotEmpty())<sup>{{ auth()->user()->unreadNotifications->count() }}</sup>@endif</summary>
                    <div class="nav-menu-panel">
                        <a @class(['is-active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">{{ __('site.nav.dashboard') }}</a>
                        <a @class(['is-active' => request()->routeIs('account.*')]) href="{{ route('account.edit') }}">{{ __('site.nav.account') }}</a>
                        <a @class(['is-active' => request()->routeIs('notifications*')]) href="{{ route('notifications') }}">{{ __('site.nav.notifications') }} @if (auth()->user()->unreadNotifications->isNotEmpty())<sup>{{ auth()->user()->unreadNotifications->count() }}</sup>@endif</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-button" type="submit">{{ __('site.nav.logout') }}</button></form>
                    </div>
                </details>
            @else
                <a href="{{ url('/login') }}">{{ __('site.nav.login') }}</a>
                <a class="nav-cta" href="{{ url('/register') }}">{{ __('site.nav.join') }}</a>
            @endauth
            <form method="POST" action="{{ route('theme.update', session('theme', 'spring')) }}" class="theme-switcher">
                @csrf
                <label for="theme-select">{{ __('site.theme_label') }}</label>
                <select id="theme-select" name="theme" onchange="this.form.action='/theme/'+this.value; this.form.submit()">
                    @foreach (__('site.themes') as $themeKey => $themeLabel)<option value="{{ $themeKey }}" @selected(session('theme', 'spring') === $themeKey)>{{ $themeLabel }}</option>@endforeach
                </select>
            </form>
        </nav>

        <div class="site-grid">
            <aside class="sidebar sidebar-left" aria-label="World navigation">
                @foreach (($sidebarModules ?? collect())->where('placement', 'left') as $module)
                    <section class="sidebar-card"><p class="eyebrow">{{ $module->title }}</p><p>{{ $module->body }}</p>@if ($module->link_text && $module->link_url)<a class="text-link" href="{{ $module->link_url }}">{{ $module->link_text }} <span aria-hidden="true">↗</span></a>@endif</section>
                @endforeach
                <section class="sidebar-card">
                    <p class="eyebrow">{{ __('site.shell.begin_here') }}</p>
                    <h2>{{ __('site.shell.find_way') }}</h2>
                    <p>{{ __('site.shell.guide_intro') }}</p>
                    <a class="text-link" href="{{ route('content.page', 'guide') }}">{{ __('site.shell.read_guide') }} <span aria-hidden="true">↗</span></a>
                </section>
                <section class="sidebar-card sidebar-card-quiet">
                    <p class="eyebrow"><a href="{{ route('content.page', 'clans') }}">{{ __('site.shell.four_paths') }}</a></p>
                    <ul class="clan-list">
                        @foreach (($worldPages ?? collect())->get('clans', collect()) as $clan)
                            <li><span class="clan-dot {{ $clan->slug }}" aria-hidden="true"></span><a href="{{ route('world.show', ['kind' => 'clans', 'slug' => $clan->slug]) }}" @if (request()->is('clans/'.$clan->slug)) aria-current="page" @endif>{{ $clan->name }}</a></li>
                        @endforeach
                    </ul>
                    @if (($worldPages ?? collect())->get('outsiders', collect())->isNotEmpty())
                        <div class="sidebar-world-group">
                            <p class="eyebrow"><a href="{{ route('content.page', 'outsiders') }}">{{ __('site.nav.outsiders') }}</a></p>
                            <ul class="clan-list">
                                @foreach ($worldPages->get('outsiders') as $outsider)
                                    <li><span class="clan-dot outsider" aria-hidden="true"></span><a href="{{ route('world.show', ['kind' => 'outsiders', 'slug' => $outsider->slug]) }}" @if (request()->is('outsiders/'.$outsider->slug)) aria-current="page" @endif>{{ $outsider->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            </aside>

            <main class="main-content">
                @if (isset($pageError) || session('error') || (isset($errors) && $errors->any()))
                    <div class="page-error" role="alert">
                        <strong>Unable to complete your request.</strong>
                        <p>{{ $pageError ?? session('error') ?? (isset($errors) ? $errors->first() : '') }}</p>
                    </div>
                @endif
                @yield('content')
            </main>

            <aside class="sidebar sidebar-right" aria-label="Cavernas activity">
                @foreach (($sidebarModules ?? collect())->where('placement', 'right') as $module)
                    <section class="sidebar-card"><p class="eyebrow">{{ $module->title }}</p><p>{{ $module->body }}</p>@if ($module->link_text && $module->link_url)<a class="text-link" href="{{ $module->link_url }}">{{ $module->link_text }} <span aria-hidden="true">↗</span></a>@endif</section>
                @endforeach
                <section class="sidebar-card bulletin">
                    <p class="eyebrow">{{ __('site.shell.field_notes') }}</p>
                    <h2>{{ __('site.shell.gates_open') }}</h2>
                    <p>{{ __('site.shell.shell_intro') }}</p>
                    <a class="text-link" href="{{ route('content.page', 'rules') }}">{{ __('site.shell.view_rules') }} <span aria-hidden="true">↗</span></a>
                </section>
                <section class="sidebar-card">
                    <div class="card-heading"><span class="status-dot"></span><span>{{ __('site.shell.at_glance') }}</span></div>
                    <dl class="stats-list">
                        <div><dt>Open stories</dt><dd>{{ number_format($atGlance['openStories']) }}</dd></div>
                        <div><dt>Living characters</dt><dd>{{ number_format($atGlance['livingCharacters']) }}</dd></div>
                        <div><dt>Clan population</dt><dd>{{ number_format($atGlance['clanPopulation']) }}</dd></div>
                        <div><dt>Outsiders</dt><dd>{{ number_format($atGlance['outsiders']) }}</dd></div>
                        <div><dt>Lowest Clan</dt><dd>{{ $atGlance['lowestClan'] }} · {{ $atGlance['lowestCount'] }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>

        <footer class="site-footer">
            <span>{{ __('site.name') }} · {{ __('site.shell.footer_tagline') }}</span>
            <span><a href="{{ route('content.page', 'rules') }}">{{ __('site.footer.rules') }}</a> · <a href="{{ route('content.page', 'privacy') }}">{{ __('site.footer.privacy') }}</a> · <a href="{{ route('content.page', 'contact') }}">{{ __('site.footer.contact') }}</a></span>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
