@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading">
    <p class="eyebrow">{{ $board->category->name }}</p>
    <h1>{{ $board->name }}</h1>
    <p>{{ $board->description }}</p>
    @auth
        @if (auth()->user()->status === 'approved' || auth()->user()->isStaff())@if (($board->slug !== 'announcements' && strtolower($board->name) !== 'announcements') || auth()->user()->isStaff())<a class="button button-primary" href="{{ route('forum.thread.create', $board) }}">Start a story <span aria-hidden="true">→</span></a>@endif @endif
    @else
        @if ($board->slug === 'advertisements' || strtolower($board->name) === 'advertisements')<a class="button button-primary" href="{{ route('forum.thread.create', $board) }}">Post an advertisement <span aria-hidden="true">→</span></a>@else<a class="text-link" href="{{ route('login') }}">Log in to write <span aria-hidden="true">↗</span></a>@endif
    @endauth
</section>
<section class="forum-category">
    <div class="forum-category-heading"><div><p class="eyebrow">{{ $board->is_ic ? 'In character' : 'Out of character' }}</p><h2>Open threads</h2></div><span>{{ $threads->total() }} stories</span></div>
    @forelse ($threads as $thread)
        <div class="forum-board"><span class="board-glyph"><img src="{{ asset('images/icons/paw_icon.png') }}" alt=""></span><span class="board-copy"><strong><a href="{{ route('forum.thread', $thread) }}">{{ $thread->title }}</a>@if ($thread->is_pinned)<small> · Pinned</small>@endif</strong><small>Started by @if ($thread->author)<x-member-link :member="$thread->author" />@else{{ $thread->guest_name }} <span class="guest-label">Guest</span>@endif · {{ $thread->created_at->diffForHumans() }}</small></span><span class="board-count">{{ $thread->posts_count }}<small>posts</small></span><a class="board-arrow" href="{{ route('forum.thread', $thread) }}" aria-label="Open {{ $thread->title }}">↗</a></div>
    @empty
        <div class="forum-empty"><h2>No stories yet.</h2><p>This board is waiting for its first set of tracks.</p></div>
    @endforelse
</section>
{{ $threads->links() }}
@endsection
