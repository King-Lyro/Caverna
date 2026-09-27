@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Clan<br><em>population.</em></h1><p>Population control counts active and inactive Clan warriors and apprentices. Outsiders and kits are excluded.</p></section>
<section class="population-admin-grid">
@foreach ($clans as $clan)
    @php($count = $counts[$clan])
    <article class="population-admin-card"><div class="population-admin-heading"><p class="eyebrow">{{ $clan }}</p><strong>{{ $count }}</strong></div><div class="population-admin-bar"><span style="width: {{ $lowest ? min(100, ($count / $lowest) * 50) : ($count ? 100 : 0) }}%"></span></div><div class="population-admin-roles">@foreach (($roles[$clan] ?? collect()) as $role)<span>{{ ucfirst($role->role) }} <strong>{{ $role->population }}</strong></span>@endforeach</div><p>{{ CavernasRules::clanCreationAllowed($clan) ? 'Creation allowed' : 'Creation paused by population balance' }}</p></article>
@endforeach
</section>
@endsection
