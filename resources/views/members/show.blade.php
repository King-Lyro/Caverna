@extends('layouts.cavernas')

@section('content')
<section class="member-profile-panel">
    <div class="member-profile-heading"><p class="eyebrow">User profile</p>@auth @if (auth()->id() === $member->id)<a class="text-link" href="{{ route('account.edit') }}">Edit profile</a>@endif @endauth</div>
    <header class="member-profile-header">
        <div class="member-profile-avatar">
            @if ($member->avatar_path)
                <img src="{{ asset('storage/'.$member->avatar_path) }}" alt="{{ $member->name }} avatar">
            @else
                <span>{{ strtoupper(substr($member->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="member-profile-identity">
            <div class="member-profile-name"><h1>{{ $member->name }}</h1><span class="member-role">{{ $member->isAdmin() ? 'Administrator' : ($member->isModerator() ? 'Moderator' : 'Member') }}</span>@if ($member->is_absent)<span class="member-role">Away</span>@endif</div>
            @if (! $member->hide_personal_info)
                <ul class="member-profile-details">
                    @if ($member->pronouns)<li>{{ $member->pronouns }}</li>@endif
                    @if ($member->age)<li>{{ $member->age }} years old</li>@endif
                    @if ($member->location)<li>{{ $member->location }}</li>@endif
                    @if ($member->timezone)<li>{{ str_replace('_', ' ', $member->timezone) }}</li>@endif
                </ul>
            @endif
            <p class="member-profile-joined">Joined {{ $member->created_at->format('F j, Y') }}</p>
        </div>
    </header>
    <div class="member-profile-stats" aria-label="Member statistics">
        <div><strong>{{ number_format($icPosts) }}</strong><span>IC posts</span></div>
        <div><strong>{{ number_format($oocPosts) }}</strong><span>OOC posts</span></div>
        <div><strong>{{ $characters->count() }}</strong><span>Characters</span></div>
    </div>
    <section class="member-profile-about">
        <h2 class="eyebrow">About</h2>
        @if ($member->hide_personal_info)
            <p class="form-intro">This member has chosen to keep personal information private.</p>
        @elseif ($member->bio)
            <div class="member-profile-bio">{!! \Illuminate\Support\Str::markdown($member->bio, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        @else
            <p class="form-intro">This member has not added a profile introduction yet.</p>
        @endif
    </section>
    <section class="member-profile-characters">
        <h2 class="eyebrow">Characters</h2>
        <div class="member-character-grid">
            @forelse ($characters as $character)
                @php($portrait = $character->images[0] ?? $character->forum_avatar_path ?? $character->avatar_path)
                <a class="member-character-card" href="{{ route('characters.show', $character) }}">
                    @if ($portrait)
                        <img src="{{ filter_var($portrait, FILTER_VALIDATE_URL) ? $portrait : asset('storage/'.$portrait) }}" alt="{{ $character->name }} portrait">
                    @else
                        <div class="member-character-placeholder">{{ strtoupper(substr($character->name, 0, 1)) }}</div>
                    @endif
                    <div class="member-character-copy"><strong>{{ $character->name }}</strong><span>{{ $character->allegiance }} · {{ str_replace('_', ' ', $character->role) }}</span><small>{{ ucfirst($character->status) }} · {{ $character->sex === 'female' ? 'She-cat' : 'Tom' }}</small></div>
                </a>
            @empty
                <p class="form-intro">No living characters are listed.</p>
            @endforelse
        </div>
    </section>
</section>
@endsection
