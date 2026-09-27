@extends('layouts.cavernas')

@section('content')
<section class="form-panel">
    <p class="eyebrow">{{ $board->name }}</p>
    <h1>Begin a<br><em>new story.</em></h1>
    <p class="form-intro">{{ $board->is_ic ? 'This is an in-character board. Your opening post must be at least 70 words.' : 'Start a conversation with the community.' }}</p>
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('forum.thread.store', $board) }}" class="cavernas-form">
        @csrf
        <label>Thread title<input name="title" value="{{ old('title') }}" required autofocus></label>
        @if ($board->is_ic)<label>Character <select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>@endif
        <label>Opening post<textarea name="body" rows="12" required>{{ old('body') }}</textarea></label>
        <button class="button button-primary" type="submit">Open the thread <span aria-hidden="true">→</span></button>
    </form>
</section>
@endsection
