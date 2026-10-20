@extends('layouts.cavernas')

@section('content')
<section class="content-page guide-page">
    <x-presentation-heading :eyebrow="$page->eyebrow" :title="$page->title" :intro="$page->intro" />
    <article class="guide-article">{!! $body !!}@isset($indexUrl)<nav class="world-article-return"><a class="text-link" href="{{ $indexUrl }}">&larr; {{ $indexLabel }}</a></nav>@endisset</article>
</section>
@endsection