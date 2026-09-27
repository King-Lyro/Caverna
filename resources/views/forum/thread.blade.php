@extends('layouts.cavernas')

@section('content')
<section class="thread-heading">
    <div>
        <p class="eyebrow">{{ $thread->board->category->name }} <span aria-hidden="true">·</span> {{ $thread->board->name }}</p>
        <h1>{{ $thread->title }}</h1>
        <p>Started by {{ $thread->author->name }} · {{ $thread->created_at->format('M j, Y') }}</p>
    </div>
    <a class="text-link" href="{{ route('forum.board', $thread->board) }}">Back to board <span aria-hidden="true">↗</span></a>
</section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<div class="thread-page">
<div class="thread-posts">
    @foreach ($posts as $post)
        @php($characterImage = $post->character?->forum_avatar_path ?: ($post->character?->images[0] ?? null))
        <article class="thread-post">
            <aside class="thread-author">
                <div class="thread-avatar">
                    @if ($characterImage)
                        <img src="{{ filter_var($characterImage, FILTER_VALIDATE_URL) ? $characterImage : asset('storage/'.$characterImage) }}" alt="{{ $post->character->name }} portrait">
                    @else
                        <span>{{ strtoupper(substr($post->character?->name ?? $post->author->name, 0, 1)) }}</span>
                    @endif
                </div>
                @if ($post->character)
                    <a class="thread-character-name" href="{{ route('characters.show', $post->character) }}">{{ $post->character->name }}</a>
                    <span class="thread-character-role">{{ $post->character->allegiance }} · {{ $post->character->role }}</span>
                    <span class="thread-character-energy">{{ $post->character->energy }}/100 energy</span>
                    <div class="thread-character-sheet">
                        <div><span>Sex</span><strong>{{ ucfirst($post->character->sex) }}</strong></div>
                        <div><span>Age</span><strong>{{ $post->character->age_moons }} moons</strong></div>
                        <div><span>Care</span><strong>{{ ucfirst($post->character->health_status ?? 'healthy') }}</strong></div>
                    </div>
                @else
                    <strong class="thread-character-name">{{ $post->author->name }}</strong>
                    <span class="thread-character-role">{{ $post->is_ic ? 'Character unavailable' : 'Out of character' }}</span>
                @endif
                <span class="thread-player">Player: {{ $post->author->name }}</span>
            </aside>
            <div class="thread-post-content">
                <div class="thread-post-meta"><span>Post {{ $loop->iteration }}</span><time datetime="{{ $post->created_at->toISOString() }}">{{ $post->created_at->format('M j, Y · g:i A') }}</time></div>
                <div class="thread-post-body">{!! nl2br(e($post->body)) !!}</div>
                @auth
                    <form method="POST" action="{{ route('forum.post.report', $post) }}" class="report-form">@csrf<input name="reason" placeholder="Report reason" required><button type="submit">Report</button></form>
                @endauth
            </div>
        </article>
    @endforeach
</div>
@if (auth()->check() && (auth()->user()->status === 'approved' || auth()->user()->role === 'admin') && ! $thread->is_locked)
    <form method="POST" action="{{ route('forum.reply.store', $thread) }}" class="cavernas-form reply-form"><div class="reply-heading"><div><p class="eyebrow">Continue the thread</p><h2>Add to the story</h2></div><span aria-hidden="true">✦</span></div>@csrf @if ($thread->board->is_ic)<label>Character <select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>@endif<label>Reply<textarea name="body" rows="9" required></textarea></label><button class="button button-primary" type="submit">Post reply <span aria-hidden="true">→</span></button></form>
@elseif ($thread->is_locked)
    <p class="form-intro">This story is closed to new replies.</p>
@endif
{{ $posts->links() }}
</div>
@endsection
