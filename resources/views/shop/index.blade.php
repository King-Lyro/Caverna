@extends('layouts.cavernas')

@section('content')
<section class="forum-heading compact-heading">
	<p class="eyebrow">{{ __('site.shop.eyebrow') }}</p>
	<h1>{!! nl2br(e(__('site.shop.title'))) !!}</h1>
	<p>{{ __('site.shop.intro') }}</p>
</section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<section class="shop-section" aria-label="Available items">
	<div class="shop-section-heading">
		<div><p class="eyebrow">{{ __('site.shop.eyebrow') }}</p><h2>{{ __('site.shop.available') }}</h2></div>
		<span>{{ $items->count() }} {{ __('site.shop.items') }}</span>
	</div>
	<div class="shop-grid">
		@forelse ($items as $item)
			<article class="shop-card">
				<div class="shop-card-body">
					<span class="shop-card-effect">{{ $item->effect ?? __('site.shop.buy') }}</span>
					<h3>{{ $item->name }}</h3>
					<p>{{ $item->description }}</p>
				</div>
				<div class="shop-card-footer">
					<div class="shop-price"><small>{{ __('site.shop.price') }}</small><strong>{{ number_format($item->cost) }} <span>{{ __('site.shop.currency') }}</span></strong></div>
					@auth
						<form method="POST" action="{{ route('shop.purchase', $item) }}">
							@csrf
							<button class="button button-primary" type="submit">{{ __('site.shop.buy') }} <span aria-hidden="true">→</span></button>
						</form>
					@else
						<a class="text-link" href="{{ route('login') }}">{{ __('site.shop.login_to_buy') }} →</a>
					@endauth
				</div>
			</article>
		@empty
			<div class="shop-empty"><h3>{{ __('site.shop.shop_empty') }}</h3></div>
		@endforelse
	</div>
</section>
@endsection
