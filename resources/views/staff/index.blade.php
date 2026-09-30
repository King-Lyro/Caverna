@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Staff control panel</p><h1>Keep Cavernas<br><em>moving.</em></h1><p>Review member needs, moderate the forum, and maintain the systems behind the story.</p></section>
<div class="staff-cp-stats">
    <a href="{{ route('staff.applications') }}"><span class="eyebrow">Applications</span><strong>{{ $pendingApplications }}</strong><small>awaiting review</small></a>
    <a href="{{ route('staff.reports') }}"><span class="eyebrow">Reports</span><strong>{{ $openReports }}</strong><small>open moderation items</small></a>
    <a href="{{ route('staff.litters') }}"><span class="eyebrow">Pregnancies</span><strong>{{ $activePregnancies }}</strong><small>currently active</small></a>
    @if (auth()->user()->isAdmin())<a href="{{ route('staff.characters') }}"><span class="eyebrow">Characters</span><strong>{{ $activeCharacters }}</strong><small>living records</small></a>@endif
</div>
<section class="admin-management-card staff-cp-start">
    <div class="section-heading"><div><p class="eyebrow">Quick access</p><h2>Common staff tasks.</h2></div></div>
    <div class="staff-cp-quick-links">
        <a href="{{ route('staff.applications') }}"><strong>Review applications</strong><span>Approve new member accounts.</span></a>
        <a href="{{ route('staff.reports') }}"><strong>Moderate reports</strong><span>Resolve reported forum content.</span></a>
        <a href="{{ route('staff.litters') }}"><strong>Review litters</strong><span>Manage kit outcomes and records.</span></a>
        @if (auth()->user()->isAdmin())<a href="{{ route('admin.content.boards') }}"><strong>Manage forums</strong><span>Update categories, boards, and placement.</span></a>@endif
    </div>
</section>
@endsection
