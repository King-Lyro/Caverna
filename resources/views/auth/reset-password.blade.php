@extends('layouts.cavernas')

@section('content')
<section class="form-panel form-panel-short">
    <x-presentation-heading :eyebrow="__('site.auth.recovery_eyebrow')" :title="__('site.auth.reset_title')" />
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
