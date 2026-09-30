@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading">
    <p class="eyebrow">{{ __('site.forum.eyebrow') }}</p>
    <h1>{!! nl2br(e(__('site.forum.title'))) !!}</h1>
    <p>{{ __('site.forum.intro') }}</p>
</section>

@forelse ($categories as $category)
    <section class="forum-category">
        <div class="forum-category-heading"><div><p class="eyebrow">{{ __('site.forum.category') }}</p><h2>{{ $category->name }}</h2></div><span>{{ $category->boards->count() }} {{ __('site.forum.boards') }}</span></div>
        @foreach ($category->boards as $board)
            <a class="forum-board" href="{{ route('forum.board', $board) }}">
                <span class="board-glyph {{ $board->is_ic ? 'board-glyph-ic' : '' }}">{{ $board->is_ic ? '✦' : '·' }}</span>
                <span class="board-copy"><strong>{{ $board->name }}</strong><small>{{ $board->description }}</small></span>
                <span class="board-count">{{ $board->threads_count }}<small>{{ __('site.forum.threads') }}</small></span>
                <span class="board-arrow" aria-hidden="true">↗</span>
            </a>
        @endforeach
    </section>
@empty
    <section class="forum-empty"><h2>The boards are being prepared.</h2><p>Staff will open the first gathering places soon.</p></section>
@endforelse
@endsection
