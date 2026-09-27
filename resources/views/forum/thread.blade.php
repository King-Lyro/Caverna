@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">{{ $thread->board->name }}</p><h1>{{ $thread->title }}</h1><p>Started by {{ $thread->author->name }}</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<div class="thread-posts">
    @foreach ($posts as $post)
        <article class="thread-post"><aside><strong>{{ $post->author->name }}</strong><small>{{ $post->created_at->format('M j, Y') }}</small></aside><div><p>{{ $post->body }}</p>@auth<form method="POST" action="{{ route('forum.post.report', $post) }}" class="report-form">@csrf<input name="reason" placeholder="Report reason" required><button type="submit">Report</button></form>@endauth</div></article>
    @endforeach
</div>
@if (auth()->check() && (auth()->user()->status === 'approved' || auth()->user()->role === 'admin') && ! $thread->is_locked)
    <form method="POST" action="{{ route('forum.reply.store', $thread) }}" class="cavernas-form reply-form"><h2>Add to the story</h2>@csrf @if ($thread->board->is_ic)<label>Character <select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>@endif<label>Reply<textarea name="body" rows="9" required></textarea></label><button class="button button-primary" type="submit">Post reply <span aria-hidden="true">→</span></button></form>
@elseif ($thread->is_locked)
    <p class="form-intro">This story is closed to new replies.</p>
@endif
{{ $posts->links() }}
@endsection
