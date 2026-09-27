@extends('layouts.cavernas')

@section('content')
<section class="content-page notifications-page">
    <div class="section-heading"><div><p class="eyebrow">Your signals</p><h1>Notifications.</h1></div>@if (auth()->user()->unreadNotifications->isNotEmpty())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="text-link" type="submit">Mark all read</button></form>@endif</div>
    <div class="notification-list">
    @forelse ($notifications as $notification)
        <article class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}"><div><strong>{{ data_get($notification->data, 'title', 'Cavernas update') }}</strong><p>{{ data_get($notification->data, 'message', 'You have a new update.') }}</p></div><div><small>{{ $notification->created_at->diffForHumans() }}</small>@if (data_get($notification->data, 'character_id') || data_get($notification->data, 'listing_id'))<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Open update ↗</button></form>@elseif (! $notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="text-link" type="submit">Mark read</button></form>@endif</div></article>
    @empty
        <div class="forum-empty"><h2>No notifications.</h2><p>Important account and story updates will appear here.</p></div>
    @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
