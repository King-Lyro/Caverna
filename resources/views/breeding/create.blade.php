@extends('layouts.cavernas')

@section('content')
<section class="form-panel"><p class="eyebrow">The next generation</p><h1>Begin a<br><em>new litter.</em></h1><p class="form-intro">Both owners must agree to the story. The female needs 50 energy and the male needs 25. Pregnancy lasts four weeks.</p>@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('breeding.store') }}" class="cavernas-form">@csrf
<label>Female character<select name="female_character_id" required><option value="">Choose a character</option>@foreach ($characters->where('sex', 'female') as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->user->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>
<label>Male character<select name="male_character_id" required><option value="">Choose a character</option>@foreach ($characters->where('sex', 'male') as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->user->name }} · {{ $character->energy }} energy</option>@endforeach</select></label>
<label class="checkbox-label"><input type="checkbox" name="mates" value="1"> The characters are mates</label><button class="button button-primary" type="submit">Record pregnancy <span aria-hidden="true">→</span></button></form></section>
@endsection
