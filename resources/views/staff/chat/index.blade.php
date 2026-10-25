@extends('layouts.staff')

@section('content')
<div class="chat-workspace">
    <section class="page-heading compact-heading">
        <p class="eyebrow">Staff control panel</p>
        <h1>{{ __('chat.title') }}</h1>
        <p>{{ __('chat.moderation_intro') }}</p>
    </section>
    @if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <nav class="chat-section-nav" aria-label="{{ __('chat.moderate') }}">
        <a href="#chat-review">{{ __('chat.review_messages') }} <span>{{ $messages->total() }}</span></a>
        <a href="#chat-mutes">{{ __('chat.active_mutes') }} <span>{{ $mutes->total() }}</span></a>
        <a href="#chat-history">{{ __('chat.history') }}</a>
        <a href="#chat-settings">{{ __('chat.guest_posting') }}</a>
    </nav>

    <section id="chat-review" class="chat-panel">
        <header class="chat-panel-heading"><h2>{{ __('chat.review_messages') }}</h2><p>{{ __('chat.messages_intro') }}</p></header>
        @forelse ($messages as $message)
            <article class="chat-review-card">
                <header class="chat-review-heading">
                    <strong>{{ $message->user?->name ?? $message->author_name }}</strong>
                    <span class="chat-label">{{ $message->is_guest ? __('chat.guest') : __('chat.member') }}</span>
                    @if ($message->trashed())<span class="chat-label">{{ __('chat.deleted_label') }}</span>@endif
                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y \a\t g:i A T') }}</time>
                </header>
                <p class="chatbox-body">{{ $message->body }}</p>
                <div class="chat-review-actions">
                    @unless ($message->trashed())
                        <details class="chat-action chat-action-danger">
                            <summary>{{ __('chat.delete') }}</summary>
                            <p>{{ __('chat.delete_help') }}</p>
                            <form class="cavernas-form" method="POST" action="{{ route('staff.chat.destroy', $message) }}" data-confirm="{{ __('chat.confirm_delete') }}">@csrf @method('DELETE')
                                <label>{{ __('chat.reason') }}<textarea name="reason" rows="2" required minlength="3" maxlength="1000" placeholder="{{ __('chat.reason_placeholder') }}"></textarea></label>
                                <button class="button" type="submit">{{ __('chat.delete') }}</button>
                            </form>
                        </details>
                    @endunless
                    @if ($message->is_guest || $message->user_id)
                        <details class="chat-action">
                            <summary>{{ __('chat.mute') }}</summary>
                            <p>{{ __('chat.mute_help') }}</p>
                            <form class="cavernas-form" method="POST" action="{{ route('staff.chat.mute', $message->id) }}">@csrf
                                <label>{{ __('chat.duration') }}<select name="duration">@foreach (['1h', '24h', '7d', 'forever'] as $duration)<option value="{{ $duration }}">{{ __('chat.'.$duration) }}</option>@endforeach</select></label>
                                <label>{{ __('chat.reason') }}<textarea name="reason" rows="2" required minlength="3" maxlength="1000" placeholder="{{ __('chat.reason_placeholder') }}"></textarea></label>
                                <button class="button button-primary" type="submit">{{ __('chat.mute') }}</button>
                            </form>
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <p class="chat-empty">{{ __('chat.empty') }}</p>
        @endforelse
        {{ $messages->withQueryString()->fragment('chat-review')->links() }}
    </section>

    <section id="chat-mutes" class="chat-panel">
        <header class="chat-panel-heading"><h2>{{ __('chat.active_mutes') }}</h2><p>{{ __('chat.mutes_intro') }}</p></header>
        @forelse ($mutes as $mute)
            <article class="chat-review-card">
                <header class="chat-review-heading">
                    <strong>{{ $mute->participant_name }}</strong>
                    <span class="chat-label">{{ $mute->guest_hash ? __('chat.guest') : __('chat.member') }}</span>
                </header>
                <p><strong>{{ __('chat.expires') }}:</strong> {{ $mute->expires_at?->format('F j, Y \a\t g:i A T') ?? __('chat.forever') }}</p>
                <p class="chat-reason"><strong>{{ __('chat.original_reason') }}:</strong> {{ $mute->reason }}</p>
                <details class="chat-action">
                    <summary>{{ __('chat.unmute') }}</summary>
                    <p>{{ __('chat.unmute_help') }}</p>
                    <form class="cavernas-form" method="POST" action="{{ route('staff.chat.unmute', $mute) }}">@csrf @method('PATCH')
                        <label>{{ __('chat.reason') }}<textarea name="reason" rows="2" required minlength="3" maxlength="1000" placeholder="{{ __('chat.reason_placeholder') }}"></textarea></label>
                        <button class="button" type="submit">{{ __('chat.unmute') }}</button>
                    </form>
                </details>
            </article>
        @empty
            <p class="chat-empty">{{ __('chat.no_mutes') }}</p>
        @endforelse
        {{ $mutes->withQueryString()->fragment('chat-mutes')->links() }}
    </section>

    <section id="chat-history" class="chat-panel">
        <header class="chat-panel-heading"><h2>{{ __('chat.history') }}</h2><p>{{ __('chat.history_intro') }}</p></header>
        <div class="chat-history-scroll">
            <table class="chat-history-table">
                <thead><tr><th scope="col">{{ __('chat.action') }} / {{ __('chat.reason') }}</th><th scope="col">{{ __('chat.participant') }}</th><th scope="col">{{ __('chat.performed_by') }}</th><th scope="col">{{ __('chat.when') }}</th></tr></thead>
                <tbody>
                    @forelse ($history as $entry)
                        @php
                            $actionLabel = match ($entry->action) {
                                'chat.message.deleted' => __('chat.history_delete'),
                                'chat.participant.muted' => __('chat.history_mute'),
                                'chat.participant.unmuted' => __('chat.history_unmute'),
                                'chat.settings.updated' => __('chat.history_settings'),
                            };
                            $participant = $entry->changes['participant_name'] ?? $entry->auditable?->participant_name ?? $entry->auditable?->author_name ?? __('chat.unavailable');
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $actionLabel }}</strong>
                                @if (isset($entry->changes['duration']))<span class="chat-label">{{ __('chat.'.$entry->changes['duration']) }}</span>@endif
                                @if ($entry->action === 'chat.settings.updated')
                                    <p>{{ !empty($entry->changes['guest_posting']) ? __('chat.enable_guests') : __('chat.disable_guests') }}</p>
                                @else
                                    <p class="chat-reason">{{ $entry->changes['reason'] ?? __('chat.reason_unrecorded') }}</p>
                                @endif
                            </td>
                            <td>{{ $entry->action === 'chat.settings.updated' ? __('chat.site_setting') : $participant }}</td>
                            <td>{{ $entry->actor?->name ?? __('chat.unavailable') }}</td>
                            <td><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y \a\t g:i A T') }}</time></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('chat.no_history') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $history->withQueryString()->fragment('chat-history')->links() }}
    </section>

    <section id="chat-settings" class="chat-panel">
        <header class="chat-panel-heading"><h2>{{ __('chat.guest_posting') }}: <span class="chat-label">{{ $guestsEnabled ? __('chat.enabled') : __('chat.disabled') }}</span></h2><p>{{ __('chat.settings_intro') }}</p></header>
        @if (auth()->user()->isAdmin())
            <form class="chat-settings-form" method="POST" action="{{ route('staff.chat.settings') }}">@csrf @method('PATCH')
                <input type="hidden" name="enabled" value="{{ $guestsEnabled ? '0' : '1' }}">
                <button class="button" type="submit">{{ $guestsEnabled ? __('chat.disable_guests') : __('chat.enable_guests') }}</button>
            </form>
        @endif
    </section>
</div>
@endsection
