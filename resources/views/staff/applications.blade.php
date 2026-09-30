@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Staff desk</p><h1>Applications<br><em>awaiting review.</em></h1><p>Read each writing sample and approve members who are ready to enter the community.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<section class="admin-management-card staff-application-queue">
    <div class="section-heading"><div><p class="eyebrow">Member queue</p><h2>Pending applications.</h2></div><span>{{ $applications->total() }} {{ $applications->total() === 1 ? 'application' : 'applications' }}</span></div>
    @forelse ($applications as $application)
        <article class="application-card staff-application-card">
            <div class="application-heading"><div><h2>{{ $application->name }}</h2><p>{{ $application->email }}</p></div><time>{{ $application->created_at->format('M j, Y') }}</time></div>
            <div class="staff-application-sample"><p class="eyebrow">Roleplay sample</p><p>{{ $application->roleplay_sample }}</p></div>
            <div class="staff-application-actions"><form method="POST" action="{{ route('staff.applications.approve', $application) }}">@csrf @method('PATCH')<button class="button button-primary" type="submit">Approve applicant <span aria-hidden="true">→</span></button></form></div>
        </article>
    @empty
        <div class="forum-empty"><h2>No applications waiting.</h2><p>The queue is clear for now.</p></div>
    @endforelse
    {{ $applications->links() }}
</section>
@endsection
