<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMute;
use App\Services\ChatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatModerationController extends Controller
{
    public function index(ChatService $chat): View
    {
        return view('staff.chat.index', [
            'messages' => ChatMessage::withTrashed()->with('user')->orderByDesc('id')->paginate(25, ['*'], 'messages_page'),
            'mutes' => ChatMute::active()->latest()->paginate(20, ['*'], 'mutes_page'),
            'guestsEnabled' => $chat->guestsEnabled(),
            'history' => AuditLog::whereIn('action', [
                'chat.message.deleted', 'chat.participant.muted', 'chat.participant.unmuted', 'chat.settings.updated',
            ])->with([
                'actor',
                'auditable' => fn (MorphTo $relation) => $relation->constrain([
                    ChatMessage::class => fn (Builder $query) => $query->withTrashed(),
                ]),
            ])->orderByDesc('id')->paginate(20, ['*'], 'history_page'),
        ]);
    }

    public function destroy(Request $request, ChatMessage $message, ChatService $chat): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        DB::transaction(function () use ($request, $message, $chat, $validated): void {
            $message->delete();
            $chat->audit($request, 'chat.message.deleted', $message, [
                ...$validated, 'participant_name' => $message->user?->name ?? $message->author_name,
            ]);
        });

        return back()->with('status', __('chat.deleted'));
    }

    public function mute(Request $request, int $message, ChatService $chat): RedirectResponse
    {
        $target = ChatMessage::withTrashed()->findOrFail($message);
        abort_unless($target->is_guest || $target->user_id, 422, __('chat.missing_account'));
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'duration' => ['required', 'in:1h,24h,7d,forever'],
        ]);
        DB::transaction(function () use ($request, $target, $chat, $validated): void {
            $mute = ChatMute::create([
                'user_id' => $target->user_id,
                'guest_hash' => $target->guest_hash,
                'ip_hash' => $target->ip_hash,
                'participant_name' => $target->author_name,
                'reason' => $validated['reason'],
                'created_by' => $request->user()->id,
                'expires_at' => match ($validated['duration']) {
                    '1h' => now()->addHour(),
                    '24h' => now()->addDay(),
                    '7d' => now()->addDays(7),
                    'forever' => null,
                },
            ]);
            $chat->audit($request, 'chat.participant.muted', $mute, [
                ...$validated, 'participant_name' => $mute->participant_name,
            ]);
        });

        return back()->with('status', __('chat.mute_saved'));
    }

    public function unmute(Request $request, ChatMute $mute, ChatService $chat): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        DB::transaction(function () use ($request, $mute, $chat, $validated): void {
            $mute->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id, 'revocation_reason' => $validated['reason']]);
            $chat->audit($request, 'chat.participant.unmuted', $mute, [
                ...$validated, 'participant_name' => $mute->participant_name,
            ]);
        });

        return back()->with('status', __('chat.unmuted'));
    }

    public function settings(Request $request, ChatService $chat): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $request->validate(['enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $chat): void {
            DB::table('site_settings')->updateOrInsert(
                ['key' => 'chat_guest_posting'],
                ['value' => $request->boolean('enabled') ? '1' : '0', 'updated_at' => now(), 'created_at' => now()],
            );
            $chat->audit($request, 'chat.settings.updated', null, ['guest_posting' => $request->boolean('enabled')]);
        });

        return back()->with('status', __('chat.settings_saved'));
    }
}
