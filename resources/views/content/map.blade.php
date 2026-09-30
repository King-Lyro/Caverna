@extends('layouts.cavernas')

@section('content')
<section class="content-page map-page">
    <div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page['eyebrow'] }}</p><h1>{!! nl2br(e($page['title'])) !!}</h1><p class="content-intro">{{ $page['intro'] }}</p></div>
    <div class="map-content">
        <div class="map-heading"><div><p class="eyebrow">Territory atlas</p><h2>Find your next scene.</h2></div>@if ($hasArtwork)<div class="map-controls"><label><input id="map-grid-toggle" type="checkbox"> Grid</label><a class="text-link" href="{{ asset('images/map.png') }}" target="_blank" rel="noopener">Full size <span aria-hidden="true">&nearr;</span></a></div>@endif</div>
        @if ($hasArtwork)
            <p class="map-caption">Select a location on the map to visit its forum.</p>
            <div class="map-viewport">
                <img src="{{ asset('images/map.png') }}" alt="Illustrated Cavernas territory showing camps, rivers, islands, forests, mountains, and paths" width="781" height="781" usemap="#territory-map">
                <div class="map-grid-overlay" aria-hidden="true"></div>
                <map name="territory-map">
                    @foreach ($locations as $location)
                        @foreach ($location['areas'] as $bounds)
                            <area shape="rect" coords="{{ implode(',', $bounds) }}" data-coords="{{ implode(',', $bounds) }}" href="{{ route('forum.board', $location['slug']) }}" alt="{{ $location['name'] }}" title="{{ $location['name'] }}">
                        @endforeach
                    @endforeach
                </map>
            </div>
        @else
            <div class="map-artwork-missing"><strong>Map artwork is being prepared.</strong><p>The territory forums are available below.</p></div>
        @endif
        <div class="map-directory">
            @foreach (['camp' => 'Clan camps', 'landmark' => 'Territory landmarks'] as $group => $label)
                <section><h3>{{ $label }}</h3><ul>@foreach ($locations as $location)@if ($location['category'] === $group)<li><a href="{{ route('forum.board', $location['slug']) }}">{{ $location['name'] }} <span aria-hidden="true">&rarr;</span></a></li>@endif @endforeach</ul></section>
            @endforeach
        </div>
    </div>
</section>
@endsection