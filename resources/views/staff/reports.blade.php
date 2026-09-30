@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Staff desk</p><h1>Open<br><em>reports.</em></h1><p>Review member concerns and record a clear moderation outcome.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<section class="admin-management-card">
<div class="section-heading"><div><p class="eyebrow">Moderation queue</p><h2>Reports awaiting review.</h2></div><span>{{ $reports->total() }} {{ $reports->total() === 1 ? 'report' : 'reports' }}</span></div>
@if ($reports->isNotEmpty())<form method="POST" action="{{ route('staff.reports.bulk-resolve') }}" class="admin-bulk-form">@csrf @method('PATCH')@endif
@forelse ($reports as $report)<article class="application-card"><div class="application-heading"><div><h2>Report from {{ $report->reporter->name }}</h2><p>{{ $report->created_at->format('M j, Y') }}</p></div></div><p class="application-sample">{{ $report->reason }}</p><div class="kit-review"><select name="reports[{{ $report->id }}][status]"><option value="">Choose outcome</option><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option></select><input name="reports[{{ $report->id }}][resolution]" placeholder="Resolution note"></div></article>@empty<div class="forum-empty"><h2>No open reports.</h2><p>New member reports will appear here for staff review.</p></div>@endforelse
@if ($reports->isNotEmpty())<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div></form>@endif
{{ $reports->links() }}
</section>
@endsection
