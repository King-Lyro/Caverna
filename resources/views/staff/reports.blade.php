@extends('layouts.cavernas')

@section('content')
<section class="form-panel staff-panel"><p class="eyebrow">Staff desk</p><h1>Open<br><em>reports.</em></h1>@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@forelse ($reports as $report)<article class="application-card"><div class="application-heading"><div><h2>Report from {{ $report->reporter->name }}</h2><p>{{ $report->created_at->format('M j, Y') }}</p></div></div><p class="application-sample">{{ $report->reason }}</p><form method="POST" action="{{ route('staff.reports.resolve', $report) }}" class="kit-review">@csrf @method('PATCH')<select name="status"><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option></select><input name="resolution" placeholder="Resolution note" required><button class="text-link" type="submit">Save</button></form></article>@empty<p class="form-intro">No open reports.</p>@endforelse
{{ $reports->links() }}</section>
@endsection
