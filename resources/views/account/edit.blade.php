@extends('layouts.cavernas')

@section('content')
<section class="form-panel account-panel">
    <p class="eyebrow">Your account</p>
    <h1>Make this place<br><em>feel like yours.</em></h1>
    @if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="cavernas-form">
        @csrf @method('PATCH')
        <label>Username<input name="name" value="{{ old('name', $user->name) }}" required></label>
        <label>Email <small>Email cannot be changed here.</small><input value="{{ $user->email }}" disabled></label>
        <label>Avatar <small>JPG, PNG, or WebP up to 1MB.</small><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
        <label>Player information<textarea name="bio" rows="6" placeholder="Tell other members about yourself.">{{ old('bio', $user->bio) }}</textarea></label>
        <div class="form-columns"><label>Pronouns<input name="pronouns" value="{{ old('pronouns', $user->pronouns) }}" placeholder="she/her"></label><label>Age<input type="number" name="age" min="13" max="120" value="{{ old('age', $user->age) }}"></label></div>
        <div class="form-columns"><label>Location<input name="location" value="{{ old('location', $user->location) }}"></label><label>Timezone<input name="timezone" value="{{ old('timezone', $user->timezone) }}" placeholder="America/New_York"></label></div>
        <div class="form-columns"><label>Discord username<input name="discord_username" value="{{ old('discord_username', $user->discord_username) }}"></label><label>Facebook URL<input type="url" name="facebook_url" value="{{ old('facebook_url', $user->facebook_url) }}"></label></div>
        <label>Instagram URL<input type="url" name="instagram_url" value="{{ old('instagram_url', $user->instagram_url) }}"></label>
        <label class="checkbox-label"><input type="checkbox" name="hide_personal_info" value="1" @checked($user->hide_personal_info)> Hide personal information from other members</label>
        <label class="checkbox-label"><input type="checkbox" name="hide_current_page" value="1" @checked($user->hide_current_page)> Hide the page I am currently viewing</label>
        <label class="checkbox-label"><input type="checkbox" name="is_absent" value="1" @checked($user->is_absent)> Mark me as currently absent</label>
        <button class="button button-primary" type="submit">Save account <span aria-hidden="true">→</span></button>
    </form>
</section>
@endsection
