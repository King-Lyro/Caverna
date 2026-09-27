@extends('layouts.cavernas')

@section('content')
<section class="content-page"><p class="eyebrow">Your economy</p><h1>Cricket<br><em>ledger.</em></h1><p class="content-intro">A transparent record of every cricket earned and spent.</p><div class="activity-table-wrap"><table class="activity-table"><thead><tr><th>Date</th><th>Activity</th><th>Amount</th></tr></thead><tbody>@forelse ($entries as $entry)<tr><td>{{ $entry->created_at->format('M j, Y') }}</td><td>{{ $entry->description }}</td><td class="ledger-amount {{ $entry->amount < 0 ? 'is-debit' : 'is-credit' }}">{{ $entry->amount > 0 ? '+' : '' }}{{ $entry->amount }}</td></tr>@empty<tr><td colspan="3">No cricket activity yet.</td></tr>@endforelse</tbody></table></div>{{ $entries->links() }}</section>
@endsection
