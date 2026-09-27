@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading">
    <p class="eyebrow">{{ $board->category->name }}</p>
    <h1>{{ $board->name }}</h1>
    <p>{{ $board->description }}</p>
    @auth
        @if (auth()->user()->status === 'approved' || auth()->user()->role === 'admin')<a class="button button-primary" href="{{ route('forum.thread.create', $board) }}">Start a story <span aria-hidden="true">→</span></a>@endif
    @else
        <a class="text-link" href="{{ route('login') }}">Log in to write <span aria-hidden="true">↗</span></a>
    @endauth
</section>
<section class="forum-category">
    <div class="forum-category-heading"><div><p class="eyebrow">{{ $board->is_ic ? 'In character' : 'Out of character' }}</p><h2>Open threads</h2></div><span>{{ $threads->total() }} stories</span></div>
    @forelse ($threads as $thread)
        <a class="forum-board" href="{{ route('forum.thread', $thread) }}"><span class="board-glyph">{{ $thread->is_pinned ? '✦' : '·' }}</span><span class="board-copy"><strong>{{ $thread->title }}</strong><small>Started by {{ $thread->author->name }} · {{ $thread->created_at->diffForHumans() }}</small></span><span class="board-count">{{ $thread->posts_count }}<small>posts</small></span><span class="board-arrow" aria-hidden="true">↗</span></a>
    @empty
        <div class="forum-empty"><h2>No stories yet.</h2><p>This board is waiting for its first set of tracks.</p></div>
    @endforelse
</section>
{{ $threads->links() }}
@endsection
