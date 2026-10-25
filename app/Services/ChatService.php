<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatService
{
    public function hash(string $value): string
    {
        return hash_hmac('sha256', 'chat:'.$value, config('app.key'));
    }

    public function identity(Request $request): array
    {
        if ($request->user()) {
            return ['user_id' => $request->user()->id, 'guest_hash' => null, 'ip_hash' => null];
        }

        $identifier = $request->attributes->get('chat_guest_id') ?? $request->cookie('chat_guest');
        if (! is_string($identifier) || ! preg_match('/^[a-zA-Z0-9]{64}$/D', $identifier)) {
            $identifier = Str::random(64);
            Cookie::queue(Cookie::make('chat_guest', $identifier, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
        }
        $request->attributes->set('chat_guest_id', $identifier);

        return [
            'user_id' => null,
            'guest_hash' => $this->hash('guest:'.$identifier),
            'ip_hash' => $this->hash('ip:'.$request->ip()),
        ];
    }

    public function cooldownKey(array $identity): string
    {
        return 'chat:post:'.($identity['user_id'] ? 'user:'.$identity['user_id'] : 'guest:'.$identity['guest_hash']);
    }

    public function guestsEnabled(): bool
    {
        return DB::table('site_settings')->where('key', 'chat_guest_posting')->value('value') === '1';
    }

    public function viewer(Request $request, array $identity): array
    {
        $mute = ChatMute::active()->where(function (Builder $query) use ($identity): void {
            if ($identity['user_id']) {
                $query->where('user_id', $identity['user_id']);
            } else {
                $query->where('guest_hash', $identity['guest_hash'])->orWhere('ip_hash', $identity['ip_hash']);
            }
        })->orderByRaw('expires_at IS NULL DESC')->orderByDesc('expires_at')->first();
        $guestsEnabled = $this->guestsEnabled();

        return [
            'is_guest' => ! $request->user(),
            'name' => $request->user()?->name,
            'can_post' => ! $mute && ($request->user() !== null || $guestsEnabled),
            'message' => $mute
                ? ($mute->expires_at ? __('chat.muted_until', ['time' => $mute->expires_at->format('F j, Y \a\t g:i A T')]) : __('chat.muted'))
                : ((! $request->user() && ! $guestsEnabled) ? __('chat.guests_disabled') : ''),
            'muted_until' => $mute?->expires_at?->toIso8601String(),
            'cooldown' => RateLimiter::availableIn($this->cooldownKey($identity)),
        ];
    }

    public function snapshot(Request $request, array $identity): array
    {
        return [
            'messages' => ChatMessage::with('user.roles')->orderByDesc('id')->limit(50)->get()->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'name' => $message->user?->name ?? $message->author_name,
                'profile_url' => $message->user ? route('members.show', $message->user) : null,
                'badge' => $message->is_guest ? __('chat.guest') : ($message->user?->isStaff() ? __('chat.staff') : __('chat.member')),
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ])->all(),
            'viewer' => $this->viewer($request, $identity),
        ];
    }

    public function audit(Request $request, string $action, ?Model $target, array $changes): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => $target?->getMorphClass(),
            'auditable_id' => $target?->getKey(),
            'changes' => $changes,
        ]);
    }
}
