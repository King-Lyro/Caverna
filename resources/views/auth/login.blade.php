@extends('layouts.cavernas')

@section('content')
<section class="form-panel form-panel-short">
    <p class="eyebrow">{{ __('site.auth.login_eyebrow') }}</p>
    <h1>{!! nl2br(e(__('site.auth.login_title'))) !!}</h1>
    @if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login') }}" class="cavernas-form">
        @csrf
        <label>{{ __('site.auth.email') }}<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>{{ __('site.auth.password') }}<input type="password" name="password" required></label>
        <label class="checkbox-label"><input type="checkbox" name="remember"> {{ __('site.auth.remember') }}</label>
        <button class="button button-primary" type="submit">{{ __('site.auth.login') }} <span aria-hidden="true">→</span></button>
    </form>
    <p class="form-footnote"><a href="{{ route('password.request') }}">{{ __('site.auth.forgot') }}</a><br>{{ __('site.auth.new_member') }} <a href="{{ route('register') }}">{{ __('site.auth.apply') }}</a>.</p>
</section>
@endsection
