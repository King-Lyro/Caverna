@extends('layouts.cavernas')

@section('content')
<section class="form-panel form-panel-short">
    <p class="eyebrow">{{ __('site.auth.recovery_eyebrow') }}</p>
    <h1>{!! nl2br(e(__('site.auth.recovery_title'))) !!}</h1>
    @if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="cavernas-form">
        @csrf
        <label>{{ __('site.auth.email') }}<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <button class="button button-primary" type="submit">{{ __('site.auth.send_reset') }} <span aria-hidden="true">→</span></button>
    </form>
    <p class="form-footnote"><a href="{{ route('login') }}">{{ __('site.auth.return_login') }}</a>.</p>
</section>
@endsection
