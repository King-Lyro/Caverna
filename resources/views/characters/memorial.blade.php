@extends('layouts.cavernas')

@section('content')
<section class="content-page"><p class="eyebrow">The remembered</p><h1>Character<br><em>memorial.</em></h1><p class="content-intro">Every life leaves a mark on Cavernas. These characters remain part of the world's history.</p><div class="character-grid">@forelse ($characters as $character)<a class="character-card" href="{{ route('characters.show', $character) }}"><div class="character-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</div><p class="eyebrow">{{ $character->allegiance }}</p><h2>{{ $character->name }}</h2><p>{{ $character->died_at?->format('M j, Y') ?? 'Remembered' }}</p></a>@empty<div class="forum-empty"><h2>The memorial is empty.</h2><p>No deceased characters have been recorded.</p></div>@endforelse</div>{{ $characters->links() }}</section>
@endsection
