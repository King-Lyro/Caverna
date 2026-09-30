@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Clan<br><em>population.</em></h1><p>Population control counts active and inactive Clan warriors and apprentices. Outsiders and kits are excluded.</p></section>
<section class="population-admin-grid">
@foreach ($clans as $clan)
    @php($count = $counts[$clan])
    @php($stats = $clanStats[$clan] ?? collect())
    <article class="population-admin-card"><div class="population-admin-heading"><p class="eyebrow">{{ $clan }}</p><strong>{{ $count }}</strong></div><div class="population-admin-bar"><span style="width: {{ $lowest ? min(100, ($count / $lowest) * 50) : ($count ? 100 : 0) }}%"></span></div><div class="population-admin-roles"><span>She-cats <strong>{{ $stats->where('sex', 'female')->sum('population') }}</strong></span><span>Toms <strong>{{ $stats->where('sex', 'male')->sum('population') }}</strong></span><span>Warriors <strong>{{ $stats->where('role', 'warrior')->sum('population') }}</strong></span><span>Apprentices <strong>{{ $stats->where('role', 'apprentice')->sum('population') }}</strong></span></div><p>{{ $creationAllowed[$clan] ? 'Creation allowed' : 'Creation paused by population balance' }}</p></article>
@endforeach
<article class="population-admin-card"><div class="population-admin-heading"><p class="eyebrow">Outsiders</p><strong>{{ $outsiderStats->sum('population') }}</strong></div><div class="population-admin-roles"><span>She-cats <strong>{{ $outsiderStats->where('sex', 'female')->sum('population') }}</strong></span><span>Toms <strong>{{ $outsiderStats->where('sex', 'male')->sum('population') }}</strong></span></div><p>Outsiders are excluded from clan creation limits.</p></article>
</section>
@endsection
