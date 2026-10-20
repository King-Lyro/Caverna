@props(['eyebrow' => '', 'title' => '', 'intro' => '', 'page' => null, 'member' => null])
@php
    $presentation = \App\Services\SiteAppearance::current();
    $page ??= \App\Support\SitePresentation::pageKey();
    $key = 'page.'.$page;
@endphp
<x-presentation-blocks :name="$key.'.before'" />
<section {{ $attributes->class(['page-heading', 'page-heading-theme']) }}>
    @if ($copy = $presentation->text($key.'.eyebrow', $eyebrow))<p class="eyebrow">{{ $copy }}</p>@endif
    <h1>{!! nl2br(e($presentation->text($key.'.title', $title))) !!}@if ($member)<br><em><x-member-link :member="$member" /></em>@endif</h1>
    @if ($copy = $presentation->text($key.'.intro', $intro))<p>{{ $copy }}</p>@endif
    <x-presentation-art :name="$key" />
    {{ $slot }}
</section>
<x-presentation-blocks :name="$key.'.after'" />
