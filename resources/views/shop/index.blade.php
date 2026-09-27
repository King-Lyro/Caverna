@extends('layouts.cavernas')

@section('content')
<section class="forum-heading compact-heading"><p class="eyebrow">{{ __('site.shop.eyebrow') }}</p><h1>{!! nl2br(e(__('site.shop.title'))) !!}</h1><p>{{ __('site.shop.intro') }}</p></section>@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<div class="shop-grid">@foreach ($items as $item)<article class="shop-card"><p class="eyebrow">{{ $item->effect ?? __('site.shop.buy') }}</p><h2>{{ $item->name }}</h2><p>{{ $item->description }}</p><div class="shop-card-footer"><strong>{{ $item->cost }} crickets</strong>@auth<form method="POST" action="{{ route('shop.purchase', $item) }}">@csrf<button class="button button-primary" type="submit">{{ __('site.shop.buy') }} <span aria-hidden="true">→</span></button></form>@else<a class="text-link" href="{{ route('login') }}">{{ __('site.shop.login_to_buy') }}</a>@endauth</div></article>@endforeach</div>
@endsection
