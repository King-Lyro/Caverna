@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Adoption<br><em>oversight.</em></h1><p>Review owner listings, monitor availability, and oversee pending auditions.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<section class="admin-management-card"><div class="section-heading"><div><p class="eyebrow">Pending applications</p><h2>Auditions awaiting review.</h2></div></div>
@forelse ($applications as $application)
<article class="admin-adoption-application"><div><strong>{{ $application->listing->title }}</strong><span>{{ $application->applicant->name }} · {{ $application->created_at->format('M j, Y') }}</span><p>{{ $application->message }}</p></div><div class="admin-adoption-actions"><form method="POST" action="{{ route('adoption.review', $application) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="button button-primary" type="submit">Approve</button></form><form method="POST" action="{{ route('adoption.review', $application) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="button" type="submit">Reject</button></form></div></article>
@empty<p class="form-intro">No adoption applications are waiting.</p>@endforelse
{{ $applications->links() }}
</section>
<section class="admin-management-card admin-adoption-listings"><div class="section-heading"><div><p class="eyebrow">All listings</p><h2>Published adoptables.</h2></div><a class="text-link" href="{{ route('adoption.index') }}">View public page ↗</a></div>
@forelse ($listings as $listing)<div class="admin-adoption-listing"><div><strong>{{ $listing->title }}</strong><span>{{ $listing->character?->name ?: 'No character' }} · Owner: {{ $listing->owner?->name ?: 'Unknown' }}</span></div><div><span class="adoption-status">{{ ucfirst($listing->status) }}</span><small>{{ ucfirst($listing->claim_policy) }}</small></div></div>@empty<p class="form-intro">No adoption listings have been published.</p>@endforelse
{{ $listings->links() }}
</section>
@endsection
