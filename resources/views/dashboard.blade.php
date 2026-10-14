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
    <article><a class="dashboard-request-link" href="{{ route('dashboard') }}#breeding-requests"><span class="eyebrow">Breeding requests</span><strong>{{ $pendingMateRequests->count() + $breedingRequests->count() }}</strong><small>awaiting response</small></a></article>
</div>

<section class="dashboard-section">
    <div class="section-heading"><div><p class="eyebrow">Your characters</p><h2>Lives in progress.</h2></div><a class="text-link" href="{{ route('characters.create') }}">Create character <span aria-hidden="true">↗</span></a></div>
    <div class="character-directory-scroll"><table class="character-directory-table">
        <thead><tr><th scope="col">Character</th><th scope="col">Sex</th><th scope="col">Age</th><th scope="col">Allegiance</th><th scope="col">Energy</th></tr></thead>
        <tbody>
        @forelse ($characters as $character)
            <tr>
                <td><a class="directory-identity" href="{{ route('characters.show', $character) }}">@php($portrait = $character->images[0] ?? $character->forum_avatar_path ?? $character->avatar_path)@if ($portrait)<img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $character->name }} portrait">@else<span class="directory-avatar">{{ strtoupper(substr($character->name, 0, 1)) }}</span>@endif<span class="directory-name"><strong>{{ $character->name }}</strong>@if ($character->status === 'inactive')<small>Inactive</small>@endif</span></a></td>
                <td>{{ $character->sex === 'female' ? 'She-cat' : 'Tom' }}</td>
                <td>{{ number_format((float) $character->age_moons, 1) }} moons</td>
                <td><span class="directory-allegiance"><span class="clan-dot {{ strtolower($character->allegiance) }}" aria-hidden="true"></span>{{ $character->allegiance }}</span></td>
                <td><span class="directory-energy" title="{{ $character->energy }} out of 100 energy"><strong>{{ $character->energy }}</strong><span class="energy-track"><span style="width: {{ $character->energy }}%"></span></span></span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="forum-empty"><h2>Your camp is quiet.</h2><p>Create your first character to begin.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

@if ($pregnancies->isNotEmpty())
<section class="dashboard-section">
    <div class="section-heading"><div><p class="eyebrow">Breeding</p><h2>Stories taking shape.</h2></div><a class="text-link" href="{{ route('breeding.create') }}">Begin another <span aria-hidden="true">↗</span></a></div>
    @foreach ($pregnancies as $pregnancy)<article class="dashboard-row"><strong>{{ $pregnancy->female->name }} & {{ $pregnancy->male->name }}</strong><span>Due {{ $pregnancy->due_at->format('M j, Y') }}</span></article>@endforeach
</section>
@endif

<section class="dashboard-section" id="breeding-requests">
    <div class="section-heading"><div><p class="eyebrow">Breeding requests</p><h2>Requests awaiting a response.</h2></div></div>
    @foreach ($breedingRequests as $breedingRequest)
        <article class="dashboard-row">
            <strong><a href="{{ route('characters.show', $breedingRequest->female) }}">{{ $breedingRequest->female->name }}</a> &amp; <a href="{{ route('characters.show', $breedingRequest->male) }}">{{ $breedingRequest->male->name }}</a></strong>
            <span>Pregnancy · Requested by <x-member-link :member="$breedingRequest->requester" /></span>
            @if ($breedingRequest->requested_by !== auth()->id())
                <form method="POST" action="{{ route('breeding.accept', $breedingRequest) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Accept</button></form>
                <form method="POST" action="{{ route('breeding.reject', $breedingRequest) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Decline</button></form>
            @else
                <span>Sent · Awaiting the other owner's response</span>
            @endif
        </article>
    @endforeach
    @foreach ($pendingMateRequests as $mateRequest)
        <article class="dashboard-row">
            <strong><a href="{{ route('characters.show', $mateRequest->fromCharacter) }}">{{ $mateRequest->fromCharacter->name }}</a> &amp; <a href="{{ route('characters.show', $mateRequest->toCharacter) }}">{{ $mateRequest->toCharacter->name }}</a></strong>
            <span>Mate pairing · Requested by <x-member-link :member="$mateRequest->fromCharacter->user" /></span>
            @if ($mateRequest->toCharacter->user_id === auth()->id())
                <form method="POST" action="{{ route('characters.mate-accept', $mateRequest) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Accept</button></form>
                <form method="POST" action="{{ route('characters.mate-reject', $mateRequest) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Decline</button></form>
            @else
                <span>Sent · Awaiting the other owner's response</span>
            @endif
        </article>
    @endforeach
    @if ($breedingRequests->isEmpty() && $pendingMateRequests->isEmpty())<p class="form-intro">No breeding requests are awaiting a response.</p>@endif
</section>

@if ($transferRequests->isNotEmpty())
<section class="dashboard-section"><div class="section-heading"><div><p class="eyebrow">Character transfers</p><h2>Requests waiting for you.</h2></div></div>@foreach ($transferRequests as $transfer)<article class="dashboard-row"><strong>{{ $transfer->character->name }}</strong><span>From <x-member-link :member="$transfer->sender" /></span><form method="POST" action="{{ route('characters.transfer.accept', $transfer) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Accept</button></form></article>@endforeach</section>
@endif

@if ($mentorRequests->isNotEmpty())
<section class="dashboard-section" id="mentor-requests"><div class="section-heading"><div><p class="eyebrow">Mentor requests</p><h2>Apprentices awaiting your answer.</h2></div></div>@foreach ($mentorRequests as $relationship)<article class="dashboard-row"><strong><a href="{{ route('characters.show', $relationship->character) }}">{{ $relationship->character->name }}</a></strong><span>Requests <a href="{{ route('characters.show', $relationship->relatedCharacter) }}">{{ $relationship->relatedCharacter->name }}</a> as mentor</span><form method="POST" action="{{ route('characters.mentor-accept', $relationship) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Accept</button></form></article>@endforeach</section>
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
