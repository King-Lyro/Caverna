@extends('layouts.cavernas')

@section('content')
<section class="content-page world-index-page">
    <div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page['eyebrow'] }}</p><h1>{!! nl2br(e($page['title'])) !!}</h1><p class="content-intro">{{ $page['intro'] }}</p></div>
    <div class="world-index-content">
        @forelse ($entries as $entry)
            <article class="world-entry">
                <div><h2>{{ $entry->name }}</h2><p>{{ $entry->summary }}</p></div>
                <a class="text-link" href="{{ route('world.show', ['kind' => $kind, 'slug' => $entry->slug]) }}">Visit {{ $entry->name }} page <span aria-hidden="true">&rarr;</span></a>
            </article>
        @empty
            <p>No pages have been added yet.</p>
        @endforelse
    </div>
</section>
@endsection