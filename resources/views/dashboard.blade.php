@extends('layouts.cavernas')

@section('content')
<section class="dashboard-heading">
    <p class="eyebrow">Your camp</p>
    <h1>Welcome back,<br><em>{{ auth()->user()->name }}.</em></h1>
    <p>Keep track of the characters, stories, and paths currently in your care.</p>
</section>

<div class="dashboard-stats">
    <article><span class="eyebrow">Crickets</span><strong>{{ number_format($crickets) }}</strong><small><a class="text-link" href="{{ route('crickets') }}">view ledger</a></small></article>
    <article><span class="eyebrow">Inventory</span><strong>{{ $inventoryCount }}</strong><small>items in hand</small></article>
    <article><span class="eyebrow">Mate requests</span><strong>{{ $pendingMateRequests }}</strong><small>awaiting response</small></article>
</div>

<section class="dashboard-section">
    <div class="section-heading"><div><p class="eyebrow">Your characters</p><h2>Lives in progress.</h2></div><a class="text-link" href="{{ route('characters.create') }}">Create character <span aria-hidden="true">↗</span></a></div>
    <div class="character-grid">
        @forelse ($characters as $character)
            <a class="character-card" href="{{ route('characters.show', $character) }}"><div class="character-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</div><p class="eyebrow">{{ $character->allegiance }}</p><h2>{{ $character->name }}</h2><p>{{ $character->looks }}</p><div class="energy-track"><span style="width: {{ $character->energy }}%"></span></div><small>{{ $character->energy }}/100 energy</small></a>
        @empty
            <div class="forum-empty"><h2>Your camp is quiet.</h2><p>Create your first character to begin.</p></div>
        @endforelse
    </div>
</section>

@if ($pregnancies->isNotEmpty())
<section class="dashboard-section">
    <div class="section-heading"><div><p class="eyebrow">Breeding</p><h2>Stories taking shape.</h2></div><a class="text-link" href="{{ route('breeding.create') }}">Begin another <span aria-hidden="true">↗</span></a></div>
    @foreach ($pregnancies as $pregnancy)<article class="dashboard-row"><strong>{{ $pregnancy->female->name }} & {{ $pregnancy->male->name }}</strong><span>Due {{ $pregnancy->due_at->format('M j, Y') }}</span></article>@endforeach
</section>
@endif

@if ($transferRequests->isNotEmpty())
<section class="dashboard-section"><div class="section-heading"><div><p class="eyebrow">Character transfers</p><h2>Requests waiting for you.</h2></div></div>@foreach ($transferRequests as $transfer)<article class="dashboard-row"><strong>{{ $transfer->character->name }}</strong><span>From {{ $transfer->sender->name }}</span><form method="POST" action="{{ route('characters.transfer.accept', $transfer) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Accept</button></form></article>@endforeach</section>
@endif

@if ($adoptionApplications->isNotEmpty())
<section class="dashboard-section"><div class="section-heading"><div><p class="eyebrow">Adoption history</p><h2>Your applications.</h2></div><a class="text-link" href="{{ route('adoption.index') }}">Browse adoptables ↗</a></div>@foreach ($adoptionApplications as $application)<article class="dashboard-row"><a href="{{ route('adoption.show', $application->listing) }}"><strong>{{ $application->listing->title }}</strong></a><span>{{ ucfirst($application->status) }}</span></article>@endforeach</section>
@endif

@if ($transferHistory->isNotEmpty() || $saleHistory->isNotEmpty())
<section class="dashboard-section"><div class="section-heading"><div><p class="eyebrow">Ownership history</p><h2>Recent movement.</h2></div></div>
    @foreach ($transferHistory as $transfer)<article class="dashboard-row"><a href="{{ route('characters.show', $transfer->character) }}"><strong>{{ $transfer->character->name }}</strong></a><span>Transfer {{ $transfer->status === 'accepted' ? 'accepted' : 'closed' }} · {{ $transfer->updated_at->format('M j, Y') }}</span></article>@endforeach
    @foreach ($saleHistory as $sale)<article class="dashboard-row"><a href="{{ route('characters.show', $sale->character) }}"><strong>{{ $sale->character->name }}</strong></a><span>{{ $sale->status === 'sold' ? 'Sold' : 'Sale withdrawn' }} · {{ $sale->updated_at->format('M j, Y') }}</span></article>@endforeach
</section>
@endif
@endsection
