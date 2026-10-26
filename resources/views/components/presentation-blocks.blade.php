@props(['name', 'defaults' => []])
@php($presentation = \App\Services\SiteAppearance::current())
@if ($boxes = $presentation->blocks($name, $defaults))
<div class="presentation-blocks" data-region="{{ $name }}">
    @foreach ($boxes as $box)
    <article class="presentation-box">
        @if (! empty($box['eyebrow']))<p class="eyebrow">{{ $box['eyebrow'] }}</p>@endif
        @if (! empty($box['title']))<h2>{{ $box['title'] }}</h2>@endif
        @if (! empty($box['body']))<div class="presentation-box-body">{!! \Illuminate\Support\Str::markdown($box['body'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@endif
        @if (! empty($box['image']))<img class="presentation-art" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($box['image']) }}" alt="{{ $box['alt'] ?? '' }}">@endif
        @if (! empty($box['link']))<a class="text-link" href="{{ $box['link'] }}">{{ ($box['label'] ?? '') ?: ($box['title'] ?? 'Read more') }} <span aria-hidden="true">&rarr;</span></a>@endif
    </article>
    @endforeach
</div>
@endif
