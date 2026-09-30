@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Applied<br><em>item audit.</em></h1><p>Review one-use shop enhancements currently attached to character profiles.</p></section>
<section class="admin-management-card admin-filter-panel"><form method="GET" class="admin-filter-form"><label>Character or item<input type="search" name="search" value="{{ $search }}" placeholder="Name"></label><button class="button button-primary" type="submit">Filter</button>@if ($search)<a class="text-link" href="{{ route('admin.items.index') }}">Clear</a>@endif</form></section>
<section class="admin-management-card"><div class="section-heading"><div><p class="eyebrow">Character enhancements</p><h2>Applied item history.</h2></div></div>
@forelse ($items as $applied)<div class="admin-item-row"><div><strong>{{ $applied->item->name }}</strong><span>{{ $applied->character->name }} · {{ $applied->character->user->name }}</span></div><time>{{ $applied->applied_at->format('M j, Y') }}</time></div>@empty<p class="form-intro">No one-use items have been applied yet.</p>@endforelse
{{ $items->links() }}</section>
@endsection
