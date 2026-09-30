@extends('layouts.cavernas')

@section('content')
<section class="content-page"><div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page['eyebrow'] }}</p><h1>{!! nl2br(e($page['title'])) !!}</h1>@if ($page['sections'])<p class="content-intro">{{ $page['intro'] }}</p>@endif</div><div class="content-sections">@forelse ($page['sections'] as $section)<article><span class="section-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $section['heading'] }}</h2><p>{{ $section['body'] }}</p></div></article>@empty<p class="content-empty">{{ $page['intro'] }}</p>@endforelse</div></section>
@endsection
