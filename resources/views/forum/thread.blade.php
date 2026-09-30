@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading thread-page-heading">
    <p class="eyebrow">{{ $thread->board->category->name }} <span aria-hidden="true">·</span> {{ $thread->board->name }}</p>
    <h1>{{ $thread->title }}</h1>
    <p>Started by {{ $thread->author->name }} · {{ $thread->created_at->format('M j, Y') }}@if ($thread->is_pinned) · Pinned @endif @if ($thread->is_locked) · Locked @endif</p>
    <a class="text-link" href="{{ route('forum.board', $thread->board) }}">Back to board <span aria-hidden="true">↗</span></a>
</section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@auth
    @if (auth()->user()->isStaff())
        <section class="staff-tools-panel" aria-label="Staff thread tools">
            <div class="section-heading"><div><p class="eyebrow">Staff tools</p><h2>Thread controls</h2></div></div>
            <div class="staff-tools-actions">
                <form method="POST" action="{{ route('forum.thread.lock', $thread) }}">@csrf @method('PATCH')<button class="button" type="submit">{{ $thread->is_locked ? 'Unlock thread' : 'Lock thread' }}</button></form>
                <form method="POST" action="{{ route('forum.thread.sticky', $thread) }}">@csrf @method('PATCH')<button class="button" type="submit">{{ $thread->is_pinned ? 'Unpin thread' : 'Pin thread' }}</button></form>
                <form method="POST" action="{{ route('forum.thread.move', $thread) }}" class="staff-move-form">@csrf @method('PATCH')<select name="forum_board_id" required><option value="">Move to board…</option>@foreach ($boards as $option)<option value="{{ $option->id }}" @selected($option->id === $thread->forum_board_id)>{{ $option->category->name }} · {{ $option->name }}</option>@endforeach</select><button class="button" type="submit">Move</button></form>
                <form method="POST" action="{{ route('forum.thread.destroy', $thread) }}" onsubmit="return confirm('Delete this entire thread?');"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="button" type="submit">Delete thread</button></form>
            </div>
        </section>
    @endif
@endauth
<div class="thread-page">
<div class="thread-posts">
    @foreach ($posts as $post)
        @php($characterImage = $post->character?->forum_avatar_path ?: ($post->character?->images[0] ?? null))
        @php($canEdit = auth()->check() && (auth()->id() === $post->user_id || auth()->user()->isStaff()))
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
                    <div class="thread-character-energybar energy-track" title="{{ $post->character->energy }} out of 100 energy"><span style="width: {{ $post->character->energy }}%"></span></div>
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
                <div class="thread-post-meta"><span>Post {{ $loop->iteration }}</span><time datetime="{{ $post->created_at->toISOString() }}">{{ $post->created_at->format('M j, Y · g:i A') }}</time>@if ($post->edited_at)<span class="thread-post-edited">Edited {{ $post->edited_at->diffForHumans() }}</span>@endif</div>
                <div data-post-display>
                    <div class="thread-post-body">{!! nl2br(e($post->body)) !!}</div>
                    @auth
                        <div class="thread-post-actions">
                            @if ($canEdit)<button class="text-link" type="button" data-post-edit-open>Edit</button>@endif
                            <button class="text-link" type="button" data-post-report-open>Report</button>
                        </div>
                    @endauth
                    @if (auth()->check() && auth()->user()->isStaff())
                        <div class="staff-tools-inline">
                            <span class="eyebrow">Staff</span>
                            <form method="POST" action="{{ route('forum.post.destroy', $post) }}" onsubmit="return confirm('Delete this post?');"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="text-link" type="submit">Delete post</button></form>
                        </div>
                    @endif
                </div>
                @auth
                    <form method="POST" action="{{ route('forum.post.report', $post) }}" class="cavernas-form post-action-panel" data-post-report-panel hidden>@csrf<label>Reason for report<textarea name="reason" rows="4" minlength="10" maxlength="2000" required></textarea></label><div class="post-action-buttons"><button class="button button-primary" type="submit">Submit report</button><button class="button" type="button" data-post-action-cancel>Cancel</button></div></form>
                @endauth
                @if ($canEdit)
                    <form method="POST" action="{{ route('forum.post.update', $post) }}" class="cavernas-form post-action-panel" data-post-edit-panel hidden>
                        @csrf @method('PATCH')
                        <label>Edit post<textarea name="body" rows="9" required>{{ $post->body }}</textarea></label>
                        <div class="post-action-buttons"><button class="button button-primary" type="submit">Update post</button><button class="button" type="button" data-post-action-cancel>Cancel</button></div>
                    </form>
                @endif
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

