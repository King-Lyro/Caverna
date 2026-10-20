@extends('layouts.cavernas')

@section('content')
<x-presentation-heading :eyebrow="$board->name" :title="'Begin a'.PHP_EOL.'new story.'" :intro="$board->is_ic ? 'This is an in-character board. Your opening post must be at least 70 words.' : 'Start a conversation with the community.'" class="compact-heading" />
<section class="form-panel">
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('forum.thread.store', $board) }}" class="cavernas-form" data-rich-form>
        @csrf
        @guest<label>Display name<input name="guest_name" value="{{ old('guest_name', session('guest_name')) }}" maxlength="80" required></label><label>What does 2+2 equal? (Type 22 if you are human.)<input name="human_answer" value="{{ old('human_answer') }}" inputmode="numeric" autocomplete="off" required></label>@endguest
        <label>Thread title<input name="title" value="{{ old('title') }}" required autofocus></label>
        @if ($board->is_ic && auth()->check())<label>Character <select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}" @selected((string) old('character_id') === (string) $character->id)>{{ $character->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>@endif
        <label>Opening post</label><textarea name="body" rows="12" required>{{ old('body') }}</textarea><div data-rich-editor data-editor-target="body" aria-label="Opening post editor"></div>
        <button class="button button-primary" type="submit">Open the thread <span aria-hidden="true">→</span></button>
    </form>
</section>
@endsection
