@extends('layouts.cavernas')

@section('content')
<div class="sales-index-page">
	<x-presentation-heading eyebrow="Character exchange" :title="'Characters'.PHP_EOL.'for sale.'" intro="Find a character whose story you want to carry forward. Listings are frozen until sold or withdrawn." class="compact-heading">@auth<a class="button button-primary" href="{{ route('sales.create') }}">{{ \App\Services\SiteAppearance::current()->text('page.sales.index.action.create', 'List a character') }} <span aria-hidden="true">→</span></a>@endauth</x-presentation-heading>
	<article class="sales-intro guide-article">{!! \Illuminate\Support\Str::markdown(\App\Services\SiteAppearance::current()->text('sales.body', \App\Support\SitePresentation::fields('page:sales.index')['sales.body']['default']), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</article>
	<section class="sales-listings adoption-listings" aria-label="Available character sales">
		<div class="adoption-listings-heading"><h2>Available characters</h2><span>{{ $listings->total() }} {{ $listings->total() === 1 ? 'listing' : 'listings' }}</span></div>
		<div class="adoption-grid">
			@forelse ($listings as $listing)
				<article class="adoption-card">
					<div class="adoption-card-image">@php($portrait = $listing->character->images[0] ?? $listing->character->forum_avatar_path)@if ($portrait)<img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $listing->character->name }} portrait">@else<span>{{ strtoupper(substr($listing->character->name, 0, 1)) }}</span>@endif</div>
					<div class="sale-entry-details"><p class="eyebrow">{{ number_format($listing->price) }} crickets</p><h3>{{ $listing->character->name }}</h3><p>{{ $listing->character->allegiance }} · Seller: <x-member-link :member="$listing->owner" /></p><div class="sale-entry-actions"><a class="text-link" href="{{ route('characters.show', $listing->character) }}">View profile</a>@auth @if ($listing->owner_id === auth()->id())<form method="POST" action="{{ route('sales.withdraw', $listing) }}">@csrf @method('PATCH')<button class="button" type="submit">Withdraw</button></form>@else<form method="POST" action="{{ route('sales.purchase', $listing) }}">@csrf<button class="button button-primary" type="submit">Purchase <span aria-hidden="true">→</span></button></form>@endif @else<a class="text-link" href="{{ route('login') }}">Log in to purchase</a>@endauth</div></div>
				</article>
			@empty
				<div class="forum-empty"><h2>No characters for sale.</h2><p>Members can list characters from their profiles or the sale page.</p></div>
			@endforelse
		</div>
	</section>
	{{ $listings->links() }}
</div>
@endsection
