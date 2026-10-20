@extends('layouts.cavernas')

@section('content')
<section class="content-page world-index-page">
    <x-presentation-heading :eyebrow="$page['eyebrow']" :title="$page['title']" :intro="$page['intro']" />
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