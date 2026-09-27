@extends('layouts.cavernas')

@section('content')
<section class="content-page"><div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page['eyebrow'] }}</p><h1>{!! nl2br(e($page['title'])) !!}</h1><p class="content-intro">{{ $page['intro'] }}</p></div><div class="content-sections">@foreach ($page['sections'] as $section)<article><span class="section-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $section['heading'] }}</h2><p>{{ $section['body'] }}</p></div></article>@endforeach</div></section>
@endsection
