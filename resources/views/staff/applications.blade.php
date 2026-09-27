@extends('layouts.cavernas')

@section('content')
<section class="form-panel staff-panel">
    <p class="eyebrow">Staff desk</p>
    <h1>Applications<br><em>awaiting review.</em></h1>
    @if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
    @forelse ($applications as $application)
        <article class="application-card">
            <div class="application-heading"><div><h2>{{ $application->name }}</h2><p>{{ $application->email }}</p></div><time>{{ $application->created_at->format('M j, Y') }}</time></div>
            <p class="application-sample">{{ $application->roleplay_sample }}</p>
            <form method="POST" action="{{ route('staff.applications.approve', $application) }}">@csrf @method('PATCH')<button class="button button-primary" type="submit">Approve applicant <span aria-hidden="true">→</span></button></form>
        </article>
    @empty
        <p class="form-intro">No applications are waiting right now.</p>
    @endforelse
    {{ $applications->links() }}
</section>
@endsection
