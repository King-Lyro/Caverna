@extends('layouts.cavernas')

@section('content')
<section class="form-panel form-panel-short">
    <p class="eyebrow">{{ __('site.auth.recovery_eyebrow') }}</p>
    <h1>{!! nl2br(e(__('site.auth.reset_title'))) !!}</h1>
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="cavernas-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>{{ __('site.auth.email') }}<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>{{ __('site.auth.new_password') }}<input type="password" name="password" required></label>
        <label>{{ __('site.auth.confirm_password') }}<input type="password" name="password_confirmation" required></label>
        <button class="button button-primary" type="submit">{{ __('site.auth.reset') }} <span aria-hidden="true">→</span></button>
    </form>
</section>
@endsection
