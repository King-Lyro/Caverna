@extends('layouts.cavernas')

@section('content')
<section class="form-panel form-panel-short"><p class="eyebrow">Character exchange</p><h1>List a<br><em>character.</em></h1><p class="form-intro">Characters listed for sale freeze in time until they are purchased or withdrawn.</p>@if ($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('sales.store') }}" class="cavernas-form">@csrf<label>Character<select name="character_id" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}">{{ $character->name }} · {{ $character->allegiance }}</option>@endforeach</select></label><label>Price in crickets<input type="number" name="price" min="1" max="1000000" required></label><button class="button button-primary" type="submit">Publish sale <span aria-hidden="true">→</span></button></form></section>
@endsection
