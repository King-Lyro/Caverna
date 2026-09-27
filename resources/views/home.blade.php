@extends('layouts.cavernas')

@section('content')
    <section class="hero-panel">
        <div class="hero-copy">
            <div class="hero-kicker"><span class="hero-kicker-mark">✦</span><span>{{ __('site.home.hero_kicker') }}</span></div>
            <h1>{!! nl2br(e(__('site.home.hero_title'))) !!}</h1>
            <p class="hero-lede">{{ __('site.home.hero_intro') }}</p>
            <div class="hero-actions">
                <a class="button button-primary" href="{{ url('/register') }}">{{ __('site.home.enter') }} <span aria-hidden="true">→</span></a>
                <a class="button button-plain" href="{{ url('/forum') }}">{{ __('site.home.visit_forum') }}</a>
            </div>
        </div>
        <div class="hero-scene p-4" aria-hidden="true">
            <div class="moon"></div>
            <div class="ridge ridge-back"></div>
            <div class="ridge ridge-front"></div>
            <div class="scene-caption">{{ __('site.home.scene_caption') }}</div>
        </div>
    </section>

    <section class="welcome-section">
        <div class="welcome-lead">
            <p class="eyebrow">{{ __('site.home.welcome_eyebrow') }}</p>
            <h2>{!! nl2br(e(__('site.home.welcome_title'))) !!}</h2>
            <p>{{ __('site.home.welcome_body') }}</p>
        </div>
        <div class="feature-row">
            <a href="{{ url('/characters') }}"><span>01</span><strong>{{ __('site.home.paths.character.title') }}</strong><small>{{ __('site.home.paths.character.body') }}</small><b>↗</b></a>
            <a href="{{ url('/clans') }}"><span>02</span><strong>{{ __('site.home.paths.allegiance.title') }}</strong><small>{{ __('site.home.paths.allegiance.body') }}</small><b>↗</b></a>
            <a href="{{ url('/forum') }}"><span>03</span><strong>{{ __('site.home.paths.story.title') }}</strong><small>{{ __('site.home.paths.story.body') }}</small><b>↗</b></a>
        </div>
    </section>

    <section class="home-overview">
        <div class="overview-card"><p class="eyebrow">{{ __('site.home.overview.paths_eyebrow') }}</p><h2>{!! nl2br(e(__('site.home.overview.paths_title'))) !!}</h2><p>{{ __('site.home.overview.paths_body') }}</p><a class="text-link" href="{{ route('content.page', 'clans') }}">{{ __('site.home.overview.paths_link') }} <span aria-hidden="true">↗</span></a></div>
        <div class="overview-card overview-card-accent"><p class="eyebrow">{{ __('site.home.overview.guide_eyebrow') }}</p><h2>{!! nl2br(e(__('site.home.overview.guide_title'))) !!}</h2><p>{{ __('site.home.overview.guide_body') }}</p><a class="text-link" href="{{ route('content.page', 'guide') }}">{{ __('site.home.overview.guide_link') }}</a></div>
    </section>
@endsection
