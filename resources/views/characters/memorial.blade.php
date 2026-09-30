@extends('layouts.cavernas')

@section('content')
<section class="content-page memorial-page">
	<div class="page-heading page-heading-theme"><p class="eyebrow">The remembered</p><h1>Character<br><em>memorial.</em></h1><p class="content-intro">Every life leaves a mark on Cavernas. These characters remain part of the world's history.</p></div>
	<div class="memorial-content">
		<div class="memorial-list">
		<div class="memorial-heading"><h2>Lives remembered</h2><span>{{ $characters->total() }} {{ $characters->total() === 1 ? 'character' : 'characters' }}</span></div>
		@forelse ($characters as $character)
			<article class="memorial-row">
				<a class="memorial-identity" href="{{ route('characters.show', $character) }}">@php($portrait = $character->images[0] ?? $character->forum_avatar_path ?? $character->avatar_path)@if ($portrait)<img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $character->name }} portrait">@else<span class="directory-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</span>@endif<span><strong>{{ $character->name }}</strong><small>{{ $character->allegiance }} · {{ ucfirst(str_replace('_', ' ', $character->role)) }}</small></span></a>
				<div><span class="eyebrow">Player</span><strong>{{ $character->user->name }}</strong></div>
				<div><span class="eyebrow">Remembered</span><time @if ($character->died_at) datetime="{{ $character->died_at->toDateString() }}" @endif>{{ $character->died_at?->format('M j, Y') ?? 'Date unknown' }}</time></div>
			</article>
		@empty
			<div class="forum-empty"><h2>The memorial is empty.</h2><p>No deceased characters have been recorded.</p></div>
		@endforelse
		</div>
	</div>
	{{ $characters->links() }}
</section>
@endsection
