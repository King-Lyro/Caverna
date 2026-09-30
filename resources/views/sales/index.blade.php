@extends('layouts.cavernas')

@section('content')
<div class="sales-index-page">
	<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Character exchange</p><h1>Characters<br><em>for sale.</em></h1><p>Find a character whose story you want to carry forward. Listings are frozen until sold or withdrawn.</p>@auth<a class="button button-primary" href="{{ route('sales.create') }}">List a character <span aria-hidden="true">→</span></a>@endauth</section>
	<article class="sales-intro guide-article"><h2>Find a new story</h2><p>Browse the characters available for purchase and read each profile before deciding. The listed price is paid in crickets; ownership transfers to the buyer when the purchase succeeds.</p></article>
	<section class="sales-listings adoption-listings" aria-label="Available character sales">
		<div class="adoption-listings-heading"><h2>Available characters</h2><span>{{ $listings->total() }} {{ $listings->total() === 1 ? 'listing' : 'listings' }}</span></div>
		<div class="adoption-grid">
			@forelse ($listings as $listing)
				<article class="adoption-card">
					<div class="adoption-card-image">@php($portrait = $listing->character->images[0] ?? $listing->character->forum_avatar_path)@if ($portrait)<img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $listing->character->name }} portrait">@else<span>{{ strtoupper(substr($listing->character->name, 0, 1)) }}</span>@endif</div>
					<div class="sale-entry-details"><p class="eyebrow">{{ number_format($listing->price) }} crickets</p><h3>{{ $listing->character->name }}</h3><p>{{ $listing->character->allegiance }} · Seller: {{ $listing->owner->name }}</p><div class="sale-entry-actions"><a class="text-link" href="{{ route('characters.show', $listing->character) }}">View profile</a>@auth @if ($listing->owner_id === auth()->id())<form method="POST" action="{{ route('sales.withdraw', $listing) }}">@csrf @method('PATCH')<button class="button" type="submit">Withdraw</button></form>@else<form method="POST" action="{{ route('sales.purchase', $listing) }}">@csrf<button class="button button-primary" type="submit">Purchase <span aria-hidden="true">→</span></button></form>@endif @else<a class="text-link" href="{{ route('login') }}">Log in to purchase</a>@endauth</div></div>
				</article>
			@empty
				<div class="forum-empty"><h2>No characters for sale.</h2><p>Members can list characters from their profiles or the sale page.</p></div>
			@endforelse
		</div>
	</section>
	{{ $listings->links() }}
</div>
@endsection
