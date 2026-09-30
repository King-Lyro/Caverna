@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading">
	<p class="eyebrow">The next generation</p>
	<h1>Begin a<br><em>new litter.</em></h1>
	<p>Record a pregnancy between two approved, unfrozen characters.</p>
</section>
<section class="form-panel">
	<div class="breeding-rules"><p><strong>Before you begin</strong></p><ul><li>The female needs at least 50 energy.</li><li>The male needs at least 25 energy.</li><li>Pregnancy lasts four weeks.</li><li>Mated characters have better litter-size odds.</li><li>Both owners must be different members.</li></ul><p class="breeding-cost-heading">Role costs</p><div class="breeding-costs"><span>Medicine Cat</span><strong>35,000 crickets</strong><span>Leader</span><strong>1,000 female / 500 male</strong><span>Deputy</span><strong>500 female / 250 male</strong><span>Other cats</span><strong>Free at 12+ moons to mate</strong></div></div>
	@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
	<form method="POST" action="{{ route('breeding.store') }}" class="cavernas-form">@csrf
	<label>Female character<select name="female_character_id" required><option value="">Choose a character</option>@foreach ($characters->where('sex', 'female') as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->user->name }} · {{ $character->energy }} energy · {{ number_format($breedingCosts[$character->id]) }} crickets</option>@endforeach</select></label>
	<label>Male character<select name="male_character_id" required><option value="">Choose a character</option>@foreach ($characters->where('sex', 'male') as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->user->name }} · {{ $character->energy }} energy · {{ number_format($breedingCosts[$character->id]) }} crickets</option>@endforeach</select></label>
	<button class="button button-primary" type="submit">Record pregnancy <span aria-hidden="true">→</span></button></form>
</section>
@endsection
