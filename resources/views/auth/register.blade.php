@extends('layouts.cavernas')

@section('content')
<section class="form-panel">
    <p class="eyebrow">{{ __('site.auth.register_eyebrow') }}</p>
    <h1>{!! nl2br(e(__('site.auth.register_title'))) !!}</h1>
    <p class="form-intro">{{ __('site.auth.register_intro') }}</p>
    @if ($errors->any())
        <div class="form-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('register') }}" class="cavernas-form">
        @csrf
        <label>{{ __('site.auth.name') }}<input name="name" value="{{ old('name') }}" required autofocus></label>
        <label>{{ __('site.auth.email') }}<input type="email" name="email" value="{{ old('email') }}" required></label>
        <div class="form-columns"><label>{{ __('site.auth.password') }}<input type="password" name="password" required></label><label>{{ __('site.auth.confirm_password') }}<input type="password" name="password_confirmation" required></label></div>
        <label>{{ __('site.auth.sample') }}<textarea name="roleplay_sample" rows="8" required>{{ old('roleplay_sample') }}</textarea><small>{{ __('site.auth.sample_help') }}</small></label>
        <button class="button button-primary" type="submit">{{ __('site.auth.submit_application') }} <span aria-hidden="true">→</span></button>
    </form>
</section>
@endsection
