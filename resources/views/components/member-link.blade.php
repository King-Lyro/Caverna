@props(['member'])
@if ($member)<a {{ $attributes->class(['member-link']) }} href="{{ route('members.show', $member) }}">{{ $member->name }}</a>@else<span>Member unavailable</span>@endif
