@extends('layouts.cavernas')

@section('content')
<section class="content-page guide-page">
    <div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page->eyebrow }}</p><h1>{!! nl2br(e($page->title)) !!}</h1><p class="content-intro">{{ $page->intro }}</p></div>
    <article class="guide-article">{!! $body !!}@isset($indexUrl)<nav class="world-article-return"><a class="text-link" href="{{ $indexUrl }}">&larr; {{ $indexLabel }}</a></nav>@endisset</article>
</section>
@endsection