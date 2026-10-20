@extends('layouts.cavernas')

@section('content')
<section class="content-page"><x-presentation-heading :eyebrow="$page['eyebrow']" :title="$page['title']" :intro="$page['intro']" /><div class="content-sections">@forelse ($page['sections'] as $section)<article><span class="section-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $section['heading'] }}</h2><p>{{ $section['body'] }}</p></div></article>@empty<p class="content-empty">{{ $page['intro'] }}</p>@endforelse</div></section>
@endsection
