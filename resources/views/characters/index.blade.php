@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">{{ __('site.characters.directory_eyebrow') }}</p><h1>{!! nl2br(e(__('site.characters.directory_title'))) !!}</h1><p>{{ __('site.characters.directory_intro') }}</p><a class="text-link" href="{{ route('characters.memorial') }}">{{ __('site.characters.memorial') }} <span aria-hidden="true">↗</span></a>@auth @if (auth()->user()->status === 'approved' || auth()->user()->role === 'admin')<a class="button button-primary" href="{{ route('characters.create') }}">{{ __('site.characters.create') }} <span aria-hidden="true">→</span></a>@endif @endauth</section>
<div class="directory-tools"><form method="GET" action="{{ route('characters') }}" class="directory-search"><input name="search" value="{{ $search }}" placeholder="{{ __('site.characters.search_placeholder') }}"><button class="button button-primary" type="submit">{{ __('site.characters.search') }}</button></form></div>
<section class="character-directory-wrap" aria-label="Character directory">
	<div class="character-directory-heading"><h2>Character list</h2><span>{{ $characters->total() }} {{ $characters->total() === 1 ? 'character' : 'characters' }}</span></div>
	<div class="character-directory-scroll"><table class="character-directory-table">
		<thead><tr><th scope="col">Character</th><th scope="col">Sex</th><th scope="col">Age</th><th scope="col">Allegiance</th><th scope="col">Energy</th><th scope="col">Player</th></tr></thead>
		<tbody>
		@forelse ($characters as $character)
			<tr>
				<td><a class="directory-identity" href="{{ route('characters.show', $character) }}">@php($portrait = $character->images[0] ?? $character->forum_avatar_path ?? $character->avatar_path)@if ($portrait)<img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $character->name }} portrait">@else<span class="directory-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</span>@endif<span class="directory-name"><strong>{{ $character->name }}</strong>@if ($character->status === 'inactive')<small>Inactive</small>@endif</span></a></td>
				<td>{{ $character->sex === 'female' ? 'She-cat' : 'Tom' }}</td>
				<td>{{ number_format((float) $character->age_moons, 1) }} moons</td>
				<td><span class="directory-allegiance"><span class="clan-dot {{ strtolower($character->allegiance) }}" aria-hidden="true"></span>{{ $character->allegiance }}</span></td>
				<td><span class="directory-energy" title="{{ $character->energy }} out of 100 energy"><strong>{{ $character->energy }}</strong><span class="energy-track"><span style="width: {{ $character->energy }}%"></span></span></span></td>
				<td>{{ $character->user->name }}</td>
			</tr>
		@empty
			<tr><td colspan="6"><div class="forum-empty"><h2>{{ __('site.characters.quiet') }}</h2><p>{{ __('site.characters.first_character') }}</p></div></td></tr>
		@endforelse
		</tbody>
	</table></div>
</section>
{{ $characters->links() }}
@endsection
