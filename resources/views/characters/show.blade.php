@extends('layouts.cavernas')

@section('content')
@php($images = $character->images ?? [])
@php($rareEyeItem = $character->appliedItems->first(fn ($applied) => $applied->item->effect === 'Rare eye color'))
@php($disabilityItem = $character->appliedItems->first(fn ($applied) => $applied->item->effect === 'Disability'))
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif

<section class="character-profile">
	<header class="profile-banner">
		<div class="profile-heading">
			<p class="eyebrow">{{ $character->allegiance }} <span aria-hidden="true">·</span> {{ $character->role }}</p>
			<h1>{{ $character->name }}</h1>
			<p class="profile-meta">{{ ucfirst($character->sex) }} <span aria-hidden="true">·</span> {{ $character->age_moons }} moons</p>
			@auth @if ($character->user_id === auth()->id())<a class="text-link profile-edit-link" href="{{ route('characters.edit', $character) }}">Edit character</a>@endif @endauth
			@if ($character->status === 'deceased')<p class="profile-deceased">Deceased{{ $character->died_at ? ' · '.$character->died_at->format('M j, Y') : '' }}</p>@elseif ($character->is_frozen)<p class="profile-deceased">Frozen{{ $character->frozen_reason ? ' · '.$character->frozen_reason : '' }}</p>@endif
		</div>
		<div class="profile-energy" aria-label="Energy: {{ $character->energy }} out of 100">
			<div class="energy-label"><span>Energy</span><strong>{{ $character->energy }}/100</strong></div>
			<div class="energy-track" title="{{ $character->energy }} out of 100 energy"><span style="width: {{ $character->energy }}%"></span></div>
		</div>
	</header>

	@if ($saleListing || $adoptionListing)
	<div class="profile-listing-banners">
		@if ($saleListing)
			<div class="profile-listing-banner"><div><span class="eyebrow">For sale</span><strong>{{ number_format($saleListing->price) }} crickets</strong></div>@auth @if ($saleListing->owner_id === auth()->id())<form method="POST" action="{{ route('sales.withdraw', $saleListing) }}">@csrf @method('PATCH')<button class="button" type="submit">Withdraw from sale</button></form>@else<form method="POST" action="{{ route('sales.purchase', $saleListing) }}">@csrf<button class="button button-primary" type="submit">Purchase <span aria-hidden="true">→</span></button></form>@endif @else<a class="text-link" href="{{ route('login') }}">Log in to purchase →</a>@endauth</div>
		@endif
		@if ($adoptionListing)
			<div class="profile-listing-banner"><div><span class="eyebrow">Up for adoption</span><strong>{{ $adoptionListing->claim_policy === 'instant' ? 'Instant claim' : 'Application required' }}</strong></div><a class="text-link" href="{{ route('adoption.show', $adoptionListing) }}">View listing <span aria-hidden="true">↗</span></a></div>
		@endif
	</div>
	@endif

	<div class="profile-layout">
		<div class="profile-media">
			<div class="profile-media-heading"><p class="eyebrow">Portraits</p><span>{{ count($images) }} {{ count($images) === 1 ? 'image' : 'images' }}</span></div>
			@if (count($images))
				<div class="profile-gallery" data-profile-gallery>
					<div class="profile-gallery-stage">
						@foreach ($images as $image)
							<img class="profile-gallery-image{{ $loop->first ? ' is-active' : '' }}" src="{{ filter_var($image, FILTER_VALIDATE_URL) ? $image : asset('storage/'.$image) }}" alt="{{ $character->name }} image {{ $loop->iteration }}" data-gallery-image="{{ $loop->index }}" @if (! $loop->first) hidden @endif>
						@endforeach
					</div>
					@if (count($images) > 1)
						<div class="profile-thumbnails" aria-label="Choose a portrait">
							@foreach ($images as $image)
								<button class="profile-thumbnail{{ $loop->first ? ' is-active' : '' }}" type="button" data-gallery-thumb="{{ $loop->index }}" aria-label="Show image {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
									<img src="{{ filter_var($image, FILTER_VALIDATE_URL) ? $image : asset('storage/'.$image) }}" alt="">
								</button>
							@endforeach
						</div>
					@endif
				</div>
			@else
				<div class="profile-gallery-empty"><span>{{ strtoupper(substr($character->name, 0, 1)) }}</span><p>No portraits have been added yet.</p></div>
			@endif
		</div>

		<div class="profile-sidebar">
			<section class="profile-facts">
				<div class="profile-fact"><span class="eyebrow">Mating</span><strong>{{ $character->mates->first()?->name ?: ($character->mate ?: 'None') }}</strong></div>
				@if ($profileStats['familyKits']->isNotEmpty() || $character->kits)<div class="profile-fact"><span class="eyebrow">Kits</span><strong>{{ $profileStats['familyKits']->pluck('name')->join(', ') ?: $character->kits }}</strong></div>@endif
				@auth
					@if (! $character->mate && (float) $character->age_moons >= 12 && $character->status === 'active' && ! $character->is_frozen && $ownedCharacters->isNotEmpty() && $character->user_id !== auth()->id())
						<form method="POST" action="{{ route('characters.mate-request', $character) }}" class="cavernas-form profile-mate-form">
							@csrf
							<label>Request with<select name="from_character_id" required><option value="">Choose your character</option>@foreach ($ownedCharacters as $owned)<option value="{{ $owned->id }}">{{ $owned->name }}</option>@endforeach</select></label>
							<button class="button button-primary" type="submit">Request mate <span aria-hidden="true">→</span></button>
						</form>
					@endif
					@php($mentorRole = strtolower(trim((string) $character->role)))
					@if (in_array($mentorRole, ['warrior', 'leader', 'deputy'], true) && $character->status === 'active' && ! $character->is_frozen && $character->user_id !== auth()->id() && $eligibleApprentices->isNotEmpty())
						<form method="POST" action="{{ route('characters.mentor-request', $character) }}" class="cavernas-form profile-mate-form">
							@csrf
							<label>Request mentorship for<select name="apprentice_character_id" required><option value="">Choose your apprentice</option>@foreach ($eligibleApprentices as $apprentice)<option value="{{ $apprentice->id }}">{{ $apprentice->name }}</option>@endforeach</select></label>
							<button class="button button-primary" type="submit">Request mentor <span aria-hidden="true">→</span></button>
						</form>
					@endif
				@endauth
			</section>

			<section class="profile-care">
				<div><span class="eyebrow">Care status</span><strong>{{ ucfirst($character->health_status ?? 'healthy') }}</strong></div>
				<p>{{ empty($character->ailments ?? []) ? 'No current ailments.' : 'Ailments: '.implode(', ', $character->ailments) }}</p>
				@auth @if ($character->user_id === auth()->id() && (float) $character->age_moons >= 12 && $character->role !== 'queen' && $character->status === 'active' && ! $character->is_frozen)<a class="text-link" href="{{ route('breeding.create') }}">Begin breeding</a>@endif @endauth
			</section>

			@auth
				@if ($character->user_id === auth()->id() && $character->status === 'active' && ! $character->is_frozen && ! $saleListing && ! $adoptionListing)
					<section class="profile-owner-actions">
						<span class="eyebrow">Character exchange</span>
						<div class="profile-owner-actions-links"><a class="text-link" href="{{ route('sales.create') }}">List for sale <span aria-hidden="true">↗</span></a><a class="text-link" href="{{ route('adoption.create') }}">Send to adoption center <span aria-hidden="true">↗</span></a></div>
						<form method="POST" action="{{ route('characters.transfer', $character) }}" class="cavernas-form profile-give-form">
							@csrf
							<label>Give to member (email)<input type="email" name="recipient_email" required></label>
							<button class="button" type="submit">Give away <span aria-hidden="true">→</span></button>
						</form>
					</section>
				@endif
			@endauth
		</div>
	</div>

</section>

<nav class="profile-tabs" aria-label="Profile information" data-profile-tabs>
	<button class="profile-tab is-active" type="button" data-profile-tab="character" aria-selected="true">Character information</button>
	<button class="profile-tab" type="button" data-profile-tab="player" aria-selected="false">Player information</button>
	<button class="profile-tab" type="button" data-profile-tab="writing" aria-selected="false">Threadlog &amp; rewards</button>
</nav>

<section class="profile-tab-panels">
	<article class="profile-information-card profile-tab-panel is-active" data-profile-panel="character">
		<div class="profile-information-heading"><p class="eyebrow">Character information</p><span aria-hidden="true">01</span></div>
		<div class="profile-character-information">
			<div class="profile-character-bios">
				<article class="profile-bio profile-bio-featured"><p class="eyebrow">Looks</p><p>{{ $character->looks }}</p></article>
				<article class="profile-bio"><p class="eyebrow">Appearance</p><p>{{ $character->appearance }}</p></article>
				<article class="profile-bio"><p class="eyebrow">Personality</p><p>{{ $character->personality }}</p></article>
				<article class="profile-bio"><p class="eyebrow">History</p><p>{{ $character->history }}</p></article>
			</div>
			<div class="profile-character-facts">
				@if ($character->appliedItems->isNotEmpty())<div class="profile-item-badges"><p class="eyebrow">Applied items</p><div>@foreach ($character->appliedItems as $applied)<div class="profile-applied-item"><span title="{{ $applied->item->description }}">@if ($applied->item->iconUrl())<img src="{{ $applied->item->iconUrl() }}" alt="">@endif{{ $applied->item->name }}</span>@auth @if ($character->user_id === auth()->id() && ! str_starts_with($applied->item->effect, 'Energy'))<form method="POST" action="{{ route('characters.items.destroy', [$character, $applied]) }}">@csrf @method('DELETE')@if ($applied->item->effect === 'Outsider access')<label>Return to<select name="clan_allegiance" required><option value="">Choose clan</option>@foreach (['ThunderClan','RiverClan','ShadowClan','WindClan'] as $clan)<option value="{{ $clan }}">{{ $clan }}</option>@endforeach</select></label>@elseif ($applied->item->effect === 'Rare eye color')<label>Eye color<input name="eye_color" maxlength="80" required></label>@endif<button type="submit" class="text-link">Remove</button></form>@endif @endauth</div>@endforeach</div></div>@endif
				<div class="profile-information-rows">
					<div><span>Status</span><strong>{{ ucfirst($character->status) }}</strong></div>
					<div><span>Role</span><strong>{{ ucfirst(str_replace('_', ' ', $character->role)) }}</strong></div>
					<div><span>Eye color</span><strong>@if ($rareEyeItem?->item->iconUrl())<img class="profile-field-icon" src="{{ $rareEyeItem->item->iconUrl() }}" alt="">@endif{{ $character->eye_color ?: 'Unknown' }}</strong></div>
					<div><span>Allegiance</span><strong>{{ $character->allegiance }}</strong></div>
					<div><span>Adopted</span><strong>{{ $character->adopted ? 'Yes' : 'No' }}</strong></div>
					<div><span>Mate</span><strong>{{ $character->mate ?: 'None' }}</strong></div>
					<div><span>IC posts</span><strong>{{ $profileStats['characterPosts'] }}</strong></div>
					<div><span>Stories joined</span><strong>{{ $profileStats['characterThreads'] }}</strong></div>
					<div><span>Rare traits</span><strong>{{ empty($character->traits ?? []) ? 'None' : implode(', ', $character->traits) }}</strong></div>
					<div><span>Cosmetic genetics</span><strong>{{ collect(['Male calico' => $character->male_calico, 'Chimera / mosaicism' => $character->chimera_mosaicism, 'Karpati / roan / salmiak' => $character->karpati_roan_salmiak, 'White sepia' => $character->white_sepia, 'Albino' => $character->albino, 'Purebred' => $character->purebred])->filter()->keys()->join(', ') ?: 'None' }}</strong></div>
					<div><span>Disability</span><strong>@if ($disabilityItem?->item->iconUrl())<img class="profile-field-icon" src="{{ $disabilityItem->item->iconUrl() }}" alt="">@endif{{ $character->disability ?: 'None' }}</strong></div>
					@if ($character->mentors->isNotEmpty())<div><span>Mentor</span><strong>{{ $character->mentors->pluck('name')->join(', ') }}</strong></div>@endif
					@if ($character->apprentices->isNotEmpty())<div><span>Apprentices</span><strong>{{ $character->apprentices->pluck('name')->join(', ') }}</strong></div>@endif
					@if ($character->parentRelationships->isNotEmpty())<div><span>Parents</span><strong>{{ $character->parentRelationships->pluck('relatedCharacter.name')->join(', ') }}</strong></div>@endif
					@if ($profileStats['siblings']->isNotEmpty())<div><span>Siblings</span><strong>{{ $profileStats['siblings']->pluck('name')->join(', ') }}</strong></div>@endif
					@if ($profileStats['familyKits']->isNotEmpty() || $character->kits)<div><span>Kits</span><strong>{{ $profileStats['familyKits']->pluck('name')->join(', ') ?: $character->kits }}</strong></div>@endif
				</div>
				<div class="profile-care profile-care-inline"><div><span class="eyebrow">Care status</span><strong>{{ ucfirst($character->health_status ?? 'healthy') }}</strong></div><p>{{ empty($character->ailments ?? []) ? 'No current ailments.' : 'Ailments: '.implode(', ', $character->ailments) }}</p></div>
			</div>
				@if ($profileStats['recentThreads']->isNotEmpty())<div class="profile-recent-threads"><p class="eyebrow">Recent roleplay</p>@foreach ($profileStats['recentThreads'] as $post)<a href="{{ route('forum.thread', $post->thread) }}">{{ $post->thread->title }} <span>{{ $post->created_at->format('M j') }}</span></a>@endforeach</div>@endif
				@if ($profileStats['ownershipHistory']->isNotEmpty())<div class="profile-recent-threads"><p class="eyebrow">Ownership history</p>@foreach ($profileStats['ownershipHistory'] as $event)<div><span>{{ str_replace('character.', '', $event->action) }} · {{ $event->created_at->format('M j, Y') }}</span></div>@endforeach</div>@endif
		</div>
	</article>

	<article class="profile-information-card profile-tab-panel" data-profile-panel="player" hidden>
		<div class="profile-information-heading"><p class="eyebrow">Player information</p><span aria-hidden="true">02</span></div>
		<div class="profile-player-copy"><strong>{{ $character->user->name }}</strong>@if (! $character->user->hide_personal_info)@if ($character->user->pronouns)<p>{{ $character->user->pronouns }}</p>@endif @if ($character->user->bio)<p>{{ $character->user->bio }}</p>@endif @else<p>Personal information is private.</p>@endif</div>
		<div class="profile-information-rows profile-player-rows"><div><span>Member since</span><strong>{{ $character->user->created_at->format('M j, Y') }}</strong></div><div><span>Posts</span><strong>{{ $profileStats['playerPosts'] }}</strong></div><div><span>Stories</span><strong>{{ $profileStats['playerThreads'] }}</strong></div></div>
	</article>

	<article class="profile-information-card profile-tab-panel profile-writing-card" data-profile-panel="writing" hidden>
		<div class="profile-information-heading"><p class="eyebrow">Threadlog &amp; rewards</p><span aria-hidden="true">03</span></div>
		<div class="profile-writing-summary"><strong>{{ $profileStats['characterPosts'] }}</strong><span>in-character posts with {{ $character->name }}</span></div>
		<div class="profile-information-rows"><div><span>Last IC post</span><strong>{{ $profileStats['lastPostAt'] ? \Illuminate\Support\Carbon::parse($profileStats['lastPostAt'])->format('M j, Y') : 'Not yet' }}</strong></div><div><span>Post-earned crickets</span><strong>{{ number_format($profileStats['postCrickets']) }}</strong></div><div><span>Cricket balance</span><strong>{{ number_format($profileStats['playerCrickets']) }}</strong></div></div>
		<p class="profile-information-note">Cavernas currently rewards writing with crickets. Skill points are not tracked separately.</p>
	</article>
</section>

@push('scripts')
<script>
document.querySelectorAll('[data-profile-gallery]').forEach((gallery) => {
	const images = gallery.querySelectorAll('[data-gallery-image]');
	const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');
	thumbs.forEach((thumb) => thumb.addEventListener('click', () => {
		const selected = thumb.dataset.galleryThumb;
		images.forEach((image) => {
			const active = image.dataset.galleryImage === selected;
			image.hidden = !active;
			image.classList.toggle('is-active', active);
		});
		thumbs.forEach((item) => {
			const active = item === thumb;
			item.classList.toggle('is-active', active);
			item.setAttribute('aria-pressed', active ? 'true' : 'false');
		});
	}));
});
document.querySelectorAll('[data-profile-tabs]').forEach((tabs) => {
	const buttons = tabs.querySelectorAll('[data-profile-tab]');
	const panels = document.querySelectorAll('[data-profile-panel]');
	buttons.forEach((button) => button.addEventListener('click', () => {
		const selected = button.dataset.profileTab;
		buttons.forEach((item) => {
			const active = item === button;
			item.classList.toggle('is-active', active);
			item.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		panels.forEach((panel) => {
			panel.hidden = panel.dataset.profilePanel !== selected;
		});
	}));
});
</script>
@endpush
@endsection
