@extends('layouts.cavernas')

@section('content')
<section class="form-panel character-form"><p class="eyebrow">Character creation</p><h1>Leave your<br><em>first tracks.</em></h1><p class="form-intro">Your first three non-adopted characters are free. Every character must be at least six moons old.</p>@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('characters.store') }}" enctype="multipart/form-data" class="cavernas-form">@csrf
<div class="form-columns"><label>Name<input name="name" value="{{ old('name') }}" required></label><label>Sex<select name="sex" required><option value="">Choose</option><option value="female">She-cat</option><option value="male">Tom</option></select></label></div>
<label>Eye color<input name="eye_color" value="{{ old('eye_color') }}" required></label>
<div class="form-columns"><label>Age in moons<input type="number" name="age_moons" min="6" step="0.5" value="{{ old('age_moons', 6) }}" required></label><label>Allegiance<select name="allegiance" required><option value="">Choose a path</option>@foreach (['ThunderClan','RiverClan','ShadowClan','WindClan'] as $allegiance)<option value="{{ $allegiance }}" @selected(old('allegiance') === $allegiance)>{{ $allegiance }}</option>@endforeach @if ($hasOutsiderPass) @foreach (['Kittypet','Loner','Rogue'] as $allegiance)<option value="{{ $allegiance }}" @selected(old('allegiance') === $allegiance)>{{ $allegiance }}</option>@endforeach @endif</select></label></div>
<label>Looks <small>15 words or fewer</small><input name="looks" value="{{ old('looks') }}" required></label>
<label>Images <small>Provide exactly three image URLs or image files.</small><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label><label>Image URLs <small>Optional when uploading files. Use three total.</small><textarea name="image_urls" rows="3" placeholder="One URL per line">{{ old('image_urls') }}</textarea>
<label>Forum avatar <small>Optional square image used beside IC posts.</small><input type="file" name="forum_avatar" accept="image/jpeg,image/png,image/webp"></label>
<label>Appearance <small>At least 250 words.</small><textarea name="appearance" rows="8" required>{{ old('appearance') }}</textarea></label><label>Personality <small>At least 250 words.</small><textarea name="personality" rows="8" required>{{ old('personality') }}</textarea></label><label>History <small>At least 250 words.</small><textarea name="history" rows="8" required>{{ old('history') }}</textarea></label>
<button class="button button-primary" type="submit">Create character <span aria-hidden="true">→</span></button></form></section>
@endsection
