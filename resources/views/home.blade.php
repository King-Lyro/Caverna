@extends('layouts.cavernas')

@section('content')
    @php($presentation = \App\Services\SiteAppearance::current())
    <x-presentation-blocks name="home.before" />
    <section @class(['hero-panel', 'hero-panel-text-only' => $presentation->get('home.image') === ''])>
        <div class="hero-copy">
            <div class="hero-kicker"><span class="hero-kicker-mark">✦</span><span>{{ $presentation->text('home.hero_kicker', __('site.home.hero_kicker')) }}</span></div>
            <h1>{!! nl2br(e($presentation->text('home.hero_title', __('site.home.hero_title')))) !!}</h1>
            <p class="hero-lede">{{ $presentation->text('home.hero_intro', __('site.home.hero_intro')) }}</p>
            <div class="hero-actions">
                <a class="button button-primary" href="{{ url('/register') }}">{{ $presentation->text('home.enter', __('site.home.enter')) }} <span aria-hidden="true">→</span></a>
                <a class="button button-plain" href="{{ url('/forum') }}">{{ $presentation->text('home.visit_forum', __('site.home.visit_forum')) }}</a>
            </div>
        </div>
        @if ($presentation->image('home.image'))
        <div class="hero-scene p-4"><x-presentation-art name="home" /><div class="scene-caption">{{ $presentation->text('home.scene_caption', __('site.home.scene_caption')) }}</div></div>
        @elseif ($presentation->get('home.image') !== '')
        <div class="hero-scene p-4" aria-hidden="true">
            <div class="moon"></div>
            <div class="ridge ridge-back"></div>
            <div class="ridge ridge-front"></div>
            <div class="scene-caption">{{ $presentation->text('home.scene_caption', __('site.home.scene_caption')) }}</div>
        </div>
        @endif
    </section>
    <x-presentation-blocks name="home.after" />

    <section @class(['welcome-section', 'welcome-section-single' => $presentation->text('home.welcome.visible', 'yes') === 'no' || $presentation->get('home.features') === []])>
        @if ($presentation->text('home.welcome.visible', 'yes') !== 'no')
        <div class="welcome-lead">
            <x-presentation-art name="home.welcome" />
            <p class="eyebrow">{{ $presentation->text('home.welcome_eyebrow', __('site.home.welcome_eyebrow')) }}</p>
            <h2>{!! nl2br(e($presentation->text('home.welcome_title', __('site.home.welcome_title')))) !!}</h2>
            <p>{{ $presentation->text('home.welcome_body', __('site.home.welcome_body')) }}</p>
        </div>
        @endif
        @if ($presentation->get('home.features') !== [])
        <div @class(['feature-row', 'feature-row-custom' => $presentation->get('home.features') !== null])>
            @if ($presentation->get('home.features') === null)
            <a href="{{ url('/characters') }}"><span>01</span><strong>{{ __('site.home.paths.character.title') }}</strong><small>{{ __('site.home.paths.character.body') }}</small><b>↗</b></a>
            <a href="{{ url('/clans') }}"><span>02</span><strong>{{ __('site.home.paths.allegiance.title') }}</strong><small>{{ __('site.home.paths.allegiance.body') }}</small><b>↗</b></a>
            <a href="{{ url('/forum') }}"><span>03</span><strong>{{ __('site.home.paths.story.title') }}</strong><small>{{ __('site.home.paths.story.body') }}</small><b>↗</b></a>
            @else
            <x-presentation-blocks name="home.features" :defaults="\App\Support\SitePresentation::featureDefaults()" />
            @endif
        </div>
        @endif
    </section>

    @if ($presentation->get('home.overview') !== [])
    <section class="home-overview">
        @if ($presentation->get('home.overview') === null)
        <div class="overview-card"><p class="eyebrow">{{ __('site.home.overview.paths_eyebrow') }}</p><h2>{!! nl2br(e(__('site.home.overview.paths_title'))) !!}</h2><p>{{ __('site.home.overview.paths_body') }}</p><a class="text-link" href="{{ route('content.page', 'clans') }}">{{ __('site.home.overview.paths_link') }} <span aria-hidden="true">↗</span></a></div>
        <div class="overview-card overview-card-accent"><p class="eyebrow">{{ __('site.home.overview.guide_eyebrow') }}</p><h2>{!! nl2br(e(__('site.home.overview.guide_title'))) !!}</h2><p>{{ __('site.home.overview.guide_body') }}</p><a class="text-link" href="{{ route('content.page', 'guide') }}">{{ __('site.home.overview.guide_link') }}</a></div>
        @else
        <x-presentation-blocks name="home.overview" :defaults="\App\Support\SitePresentation::overviewDefaults()" />
        @endif
    </section>
    @endif
@endsection
