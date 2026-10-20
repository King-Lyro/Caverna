@extends('layouts.cavernas')

@section('content')
<section class="form-panel adoption-create"><x-presentation-heading eyebrow="Owner tools" :title="'Create an'.PHP_EOL.'adoptable.'" intro="Share a character with the community. Application listings let you review auditions; instant claims transfer the character as soon as another approved member claims them." />
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('adoption.store') }}" class="cavernas-form">@csrf
<label>Character<select name="character_id" id="adoption-character" required><option value="">Choose a character</option>@foreach ($characters as $character)<option value="{{ $character->id }}" data-allegiance="{{ $character->allegiance }}" @selected((string) old('character_id') === (string) $character->id)>{{ $character->name }} · {{ $character->allegiance }}</option>@endforeach</select></label>
<label>Current Allegiance<input id="adoption-allegiance" name="current_allegiance" value="{{ old('current_allegiance') }}" placeholder="Choose a character" readonly><small>Uses the selected character's current allegiance.</small></label>
<div class="form-columns"><label>Listing title<input name="title" value="{{ old('title') }}" required></label><label>Claim method<select name="claim_policy"><option value="application">Application and audition</option><option value="instant">Instant claim</option></select></label></div>
<label>Listing description<textarea name="description" rows="5" required>{{ old('description') }}</textarea></label>
<div class="form-columns"><label>Birthplace<input name="birthplace" value="{{ old('birthplace') }}"></label><label>Parents<input name="parents" value="{{ old('parents') }}"></label></div>
<div class="form-columns"><label>Size and build<input name="size_build" value="{{ old('size_build') }}"></label><label>Eyes<input name="eyes" value="{{ old('eyes') }}"></label></div>
<label>Siblings<input name="siblings" value="{{ old('siblings') }}"></label>
<label>Coloration<textarea name="coloration" rows="3">{{ old('coloration') }}</textarea></label>
<label>Appearance<textarea name="appearance" rows="7">{{ old('appearance') }}</textarea></label>
<label>Personality<textarea name="personality" rows="7">{{ old('personality') }}</textarea></label>
<label>History<textarea name="history" rows="9">{{ old('history') }}</textarea></label>
<label>Adopter notes<textarea name="adopter_notes" rows="4" placeholder="What can the adopter change or decide?"></textarea></label>
<label>Contact instructions<textarea name="contact_instructions" rows="4" placeholder="How should interested members contact you?"></textarea></label>
<label>Eligibility<textarea name="eligibility" rows="4" placeholder="Any member or story requirements."></textarea></label>
<button class="button button-primary" type="submit">Publish adoptable <span aria-hidden="true">→</span></button>
</form></section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const character = document.getElementById('adoption-character');
    const allegiance = document.getElementById('adoption-allegiance');
    const updateAllegiance = () => {
        allegiance.value = character.selectedOptions[0]?.dataset.allegiance || '';
    };
    character.addEventListener('change', updateAllegiance);
    updateAllegiance();
});
</script>
@endsection
