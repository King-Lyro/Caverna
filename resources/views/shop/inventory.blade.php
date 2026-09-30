@extends('layouts.cavernas')

@section('content')
<section class="forum-heading compact-heading"><p class="eyebrow">Your belongings</p><h1>Your<br><em>inventory.</em></h1><p>Items you have earned or purchased are kept here.</p></section>
<div class="shop-grid">
	@forelse ($items as $inventory)
		<article class="shop-card inventory-card">
			@if ($inventory->item->iconUrl())<img class="shop-card-icon" src="{{ $inventory->item->iconUrl() }}" alt="">@endif
			<p class="eyebrow">Quantity {{ $inventory->quantity }}</p><h2>{{ $inventory->item->name }}</h2><p>{{ $inventory->item->description }}</p>
			<form method="POST" action="{{ route('inventory.use', $inventory) }}" class="cavernas-form">@csrf
				<label>Character<select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>
				@if ($inventory->item->effect === 'Rare eye color')<label>Eye color<input name="eye_color" maxlength="80" required></label>@endif
				@if ($inventory->item->effect === 'Disability')<label>Disability<input name="disability" maxlength="255" required></label>@endif
				@if ($inventory->item->effect === 'Outsider access')<label>Outsider role<select name="outsider_role" required><option value="">Choose role</option>@foreach (['kittypet' => 'Kittypet', 'loner' => 'Loner', 'rogue' => 'Rogue'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>@endif
				<button class="button button-primary" type="submit">Use item <span aria-hidden="true">→</span></button>
			</form>
		</article>
	@empty
		<div class="forum-empty"><h2>Your inventory is empty.</h2><p>Visit the shop when you are ready.</p></div>
	@endforelse
</div>
@endsection
