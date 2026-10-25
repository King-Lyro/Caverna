<x-presentation-blocks name="sidebar.chat.before" />
<section class="sidebar-card chatbox" data-chatbox data-feed="{{ route('chat.index') }}">
    <x-presentation-art name="sidebar.chat" />
    <header class="chatbox-heading">
        <h2>{{ $appearance->text('shell.chat_title', __('chat.title')) }}</h2>
        <button type="button" class="button" data-chat-toggle aria-expanded="true" aria-controls="sidebar-chat-content" aria-label="{{ __('chat.toggle') }}">−</button>
    </header>
    <div id="sidebar-chat-content" data-chat-content>
        <noscript><p>{{ __('chat.noscript') }}</p></noscript>
        <p data-chat-status role="status">{{ __('chat.loading') }}</p>
        <ol class="chatbox-messages" data-chat-messages aria-label="{{ __('chat.messages') }}" tabindex="0"></ol>
        <p class="chatbox-error" data-chat-error role="alert" hidden></p>
        <p data-chat-viewer role="status"></p>
        <form method="POST" action="{{ route('chat.store') }}" data-chat-form>
            @csrf
            @guest
                <label>{{ __('chat.name') }}<input name="name" maxlength="40" required autocomplete="nickname"></label>
            @endguest
            <label>{{ __('chat.message') }}<textarea name="body" rows="3" maxlength="500" required></textarea></label>
            <div class="chatbox-actions">
                <button class="button button-primary" type="submit" data-chat-send disabled>{{ __('chat.send') }}</button>
                <button class="button" type="button" data-chat-refresh>{{ __('chat.refresh') }}</button>
            </div>
        </form>
        @if (auth()->user()?->isStaff())
            <a class="text-link" href="{{ route('staff.chat.index') }}">{{ __('chat.moderate') }}</a>
        @endif
    </div>
    <script type="application/json" data-chat-labels>@json(\Illuminate\Support\Facades\Lang::get('chat'))</script>
</section>
<x-presentation-blocks name="sidebar.chat.after" />
