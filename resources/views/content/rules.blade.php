@extends('layouts.cavernas')

@section('content')
<section class="content-page rules-page">
    <div class="page-heading page-heading-theme"><p class="eyebrow">{{ $page['eyebrow'] }}</p><h1>{!! nl2br(e($page['title'])) !!}</h1><p class="content-intro">{{ $page['intro'] }}</p></div>
    <div class="rules-content">
        @php($ruleNumber = 1)
        @forelse ($categories as $category)
            <section class="rule-category" aria-labelledby="rule-category-{{ $category->id }}">
                <div class="rule-category-heading"><h2 id="rule-category-{{ $category->id }}">{{ $category->name }}</h2></div>
                <ol class="rule-list" start="{{ $ruleNumber }}">
                    @foreach ($category->rules as $rule)
                        <li class="rule-entry"><h3>{{ $rule->title }}</h3><p>{{ $rule->description }}</p></li>
                    @endforeach
                </ol>
                @php($ruleNumber += $category->rules->count())
            </section>
        @empty
            <p class="rule-empty">Rules will be posted here soon.</p>
        @endforelse
    </div>
</section>
@endsection