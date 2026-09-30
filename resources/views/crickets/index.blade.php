@extends('layouts.cavernas')

@section('content')
<section class="content-page">
    <div class="page-heading page-heading-theme compact-heading">
        <p class="eyebrow">Your economy</p>
        <h1>Cricket<br><em>ledger.</em></h1>
        <p>A transparent record of every cricket earned and spent.</p>
    </div>
    <div class="activity-list-content">
        <section class="activity-list-section" aria-labelledby="ledger-list-title">
            <div class="activity-list-heading"><h2 id="ledger-list-title">Recent activity</h2><span>{{ $entries->total() }} {{ $entries->total() === 1 ? 'entry' : 'entries' }}</span></div>
            <div class="activity-table-wrap">
                <table class="activity-table">
                    <thead><tr><th scope="col">Date</th><th scope="col">Activity</th><th scope="col">Amount</th></tr></thead>
                    <tbody>
                    @forelse ($entries as $entry)
                        <tr><td>{{ $entry->created_at?->format('M j, Y') ?? 'Date unavailable' }}</td><td>{{ $entry->description }}</td><td class="ledger-amount {{ $entry->amount < 0 ? 'is-debit' : 'is-credit' }}">{{ $entry->amount > 0 ? '+' : '' }}{{ $entry->amount }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="activity-empty">No cricket activity yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    {{ $entries->links() }}
</section>
@endsection
