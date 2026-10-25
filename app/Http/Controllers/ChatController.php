<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public function index(Request $request, ChatService $chat): JsonResponse
    {
        return response()->json($chat->snapshot($request, $chat->identity($request)))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, ChatService $chat): JsonResponse
    {
        $request->merge([
            'body' => is_string($request->input('body')) ? trim($request->input('body')) : $request->input('body'),
            'name' => $request->user() ? null : (is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name')),
        ]);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500', 'regex:/\S/u'],
            'name' => [$request->user() ? 'nullable' : 'required', 'string', 'max:40', 'regex:/\S/u'],
        ]);
        $identity = $chat->identity($request);
        $key = $chat->cooldownKey($identity);
        $result = Cache::lock($key.':lock', 10)->get(function () use ($request, $chat, $identity, $key, $validated): JsonResponse {
            $viewer = $chat->viewer($request, $identity);
            abort_unless($viewer['can_post'], 403, $viewer['message']);
            if (RateLimiter::tooManyAttempts($key, 1)) {
                return response()->json(['message' => __('chat.cooldown'), 'retry_after' => RateLimiter::availableIn($key)], 429);
            }

            ChatMessage::create([
                ...$identity,
                'is_guest' => ! $request->user(),
                'author_name' => $request->user()?->name ?? $validated['name'],
                'body' => $validated['body'],
            ]);
            RateLimiter::hit($key, 5);

            return response()->json($chat->snapshot($request, $identity), 201);
        });

        return ($result ?: response()->json(['message' => __('chat.cooldown'), 'retry_after' => 5], 429))
            ->header('Cache-Control', 'private, no-store');
    }
}
