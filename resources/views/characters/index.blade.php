@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">{{ __('site.characters.directory_eyebrow') }}</p><h1>{!! nl2br(e(__('site.characters.directory_title'))) !!}</h1><p>{{ __('site.characters.directory_intro') }}</p><a class="text-link" href="{{ route('characters.memorial') }}">{{ __('site.characters.memorial') }} <span aria-hidden="true">↗</span></a>@auth @if (auth()->user()->status === 'approved' || auth()->user()->role === 'admin')<a class="button button-primary" href="{{ route('characters.create') }}">{{ __('site.characters.create') }} <span aria-hidden="true">→</span></a>@endif @endauth<form method="GET" action="{{ route('characters') }}" class="directory-search"><input name="search" value="{{ $search }}" placeholder="{{ __('site.characters.search_placeholder') }}"><button class="button button-primary" type="submit">{{ __('site.characters.search') }}</button></form></section>
<div class="character-grid">
@forelse ($characters as $character)
<a class="character-card" href="{{ route('characters.show', $character) }}"><div class="character-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</div><p class="eyebrow">{{ $character->allegiance }}</p><h2>{{ $character->name }}</h2><p>{{ $character->looks }}</p><div class="energy-track"><span style="width: {{ $character->energy }}%"></span></div><small>{{ $character->energy }}/100 energy</small></a>
@empty
<div class="forum-empty"><h2>{{ __('site.characters.quiet') }}</h2><p>{{ __('site.characters.first_character') }}</p></div>
@endforelse
</div>
{{ $characters->links() }}
@endsection
