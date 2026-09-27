@extends('layouts.cavernas')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Adoption listing</p><h1>{{ $listing->title }}</h1><p>{{ $listing->description }}</p></section>
<section class="adoption-detail">
    <div class="adoption-detail-main"><p class="eyebrow">About this character</p><p>{{ $listing->eligibility ?: 'Open to approved members.' }}</p><div class="adoption-facts">@foreach (['Sex' => $listing->character?->sex, 'Birthplace' => $listing->birthplace, 'Parents' => $listing->parents, 'Size and build' => $listing->size_build, 'Eyes' => $listing->eyes, 'Siblings' => $listing->siblings, 'Spirit symbol' => $listing->spirit_symbol] as $label => $value) @if ($value)<div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>@endif @endforeach</div>@foreach (['Appearance' => $listing->appearance, 'Personality' => $listing->personality, 'History' => $listing->history, 'Adopter notes' => $listing->adopter_notes] as $label => $value) @if ($value)<div class="adoption-copy"><p class="eyebrow">{{ $label }}</p><p>{{ $value }}</p></div>@endif @endforeach @if ($listing->contact_instructions)<div class="adoption-contact"><p class="eyebrow">Contact</p><p>{{ $listing->contact_instructions }}</p>@if ($listing->owner)<strong>{{ $listing->owner->name }}</strong>@endif</div>@endif @if ($listing->character)<a class="text-link" href="{{ route('characters.show', $listing->character) }}">View character profile ↗</a>@endif</div>
    <aside class="adoption-action"><p class="eyebrow">{{ $listing->claim_policy === 'instant' ? 'Instant claim' : 'Application required' }}</p><h2>{{ $listing->claim_policy === 'instant' ? 'Make a new home.' : 'Tell staff about your camp.' }}</h2>@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif @if ($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
    @auth
        @if ($listing->owner_id === auth()->id())<form method="POST" action="{{ route('adoption.withdraw', $listing) }}">@csrf @method('PATCH')<button class="button" type="submit">Withdraw listing</button></form>@elseif ($listing->claim_policy === 'instant')
        <form method="POST" action="{{ route('adoption.claim', $listing) }}">@csrf<button class="button button-primary" type="submit">Claim character <span aria-hidden="true">→</span></button></form>@else<form method="POST" action="{{ route('adoption.apply', $listing) }}" class="cavernas-form"><label>Application message<textarea name="message" rows="8" required placeholder="Tell staff why this character belongs in your story."></textarea></label><button class="button button-primary" type="submit">Send application <span aria-hidden="true">→</span></button></form>@endif
    @else
        <a class="button button-primary" href="{{ route('login') }}">Log in to continue <span aria-hidden="true">→</span></a>
    @endauth</aside>
</section>
@endsection
