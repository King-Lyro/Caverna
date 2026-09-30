@extends('layouts.cavernas')

@section('content')
<section class="error-content">
    <p class="eyebrow">{{ $status }} · {{ $status === 404 ? 'Not found' : 'Request unavailable' }}</p>
    <h1>{{ $status === 404 ? 'This path is not here.' : 'This page is unavailable.' }}</h1>
    <p>You can return to a page you have access to or explore the rest of Cavernas.</p>
    <a class="button button-primary" href="{{ route('home') }}">Return home</a>
</section>
@endsection