<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMute;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_guest_identity_is_private_and_cookie_is_issued(): void
    {
        $this->getJson(route('chat.index'))->assertOk()->assertCookie('chat_guest')
            ->assertJsonPath('viewer.is_guest', true)->assertJsonPath('viewer.can_post', true);
        $response = $this->withCookie('chat_guest', str_repeat('a', 64))
            ->postJson(route('chat.store'), ['name' => 'Visitor', 'body' => 'Hello!']);
        $response->assertCreated()->assertJsonPath('messages.0.name', 'Visitor')
            ->assertJsonPath('messages.0.badge', 'Guest')->assertJsonPath('messages.0.profile_url', null)
            ->assertJsonPath('messages.0.avatar_url', null);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(['id', 'name', 'profile_url', 'avatar_url', 'badge', 'body', 'created_at'], array_keys($response->json('messages.0')));
        $message = ChatMessage::firstOrFail();
        $this->assertNotNull($message->guest_hash);
        $this->assertNotNull($message->ip_hash);
        $this->assertNotSame('127.0.0.1', $message->ip_hash);
        $this->assertArrayNotHasKey('guest_hash', $message->toArray());
    }

    public function test_account_identity_cannot_be_forged_and_pending_members_can_post(): void
    {
        $member = User::factory()->create(['name' => 'Real Member', 'status' => 'pending']);
        $this->actingAs($member)->postJson(route('chat.store'), [
            'name' => ['invalid but ignored'],
            'user_id' => 999,
            'is_guest' => true,
            'badge' => 'Staff',
            'body' => '<script>alert(1)</script>',
        ])->assertCreated()->assertJsonPath('messages.0.name', 'Real Member')
            ->assertJsonPath('messages.0.badge', 'Member')
            ->assertJsonPath('messages.0.profile_url', route('members.show', $member));
        $this->assertDatabaseHas('chat_messages', ['user_id' => $member->id, 'is_guest' => false]);
        $member->forceFill(['role' => 'moderator'])->save();
        $this->getJson(route('chat.index'))->assertJsonPath('messages.0.badge', 'Staff');
    }

    public function test_member_chat_avatar_uses_current_profile_avatar_and_default_and_ignores_posted_avatar(): void
    {
        $member = User::factory()->create(['avatar_path' => 'avatars/member.png']);
        $this->actingAs($member)->postJson(route('chat.store'), [
            'body' => 'Hello', 'avatar_url' => 'https://example.com/forged.png',
        ])->assertCreated()->assertJsonPath('messages.0.avatar_url', $member->avatarUrl());
        $member->update(['avatar_path' => 'avatars/updated.png']);
        $this->getJson(route('chat.index'))->assertJsonPath('messages.0.avatar_url', $member->avatarUrl());
        $member->update(['avatar_path' => null]);
        $this->getJson(route('chat.index'))->assertJsonPath('messages.0.avatar_url', asset('images/profile_default.png'));
        $member->delete();
        $this->getJson(route('chat.index'))->assertJsonPath('messages.0.avatar_url', null);
    }

    public function test_validation_rejects_wrong_types_blank_content_and_over_limits(): void
    {
        foreach ([
            ['name' => 'Guest', 'body' => '   '],
            ['name' => 'Guest', 'body' => "\u{00a0}"],
            ['name' => 'Guest', 'body' => str_repeat('x', 501)],
            ['name' => 'Guest', 'body' => ['array']],
            ['name' => '', 'body' => 'Hi'],
            ['name' => str_repeat('n', 41), 'body' => 'Hi'],
            ['name' => ['array'], 'body' => 'Hi'],
        ] as $input) {
            $this->postJson(route('chat.store'), $input)->assertUnprocessable();
        }
        $this->assertDatabaseCount('chat_messages', 0);
        $this->postJson(route('chat.store'), ['name' => str_repeat('n', 40), 'body' => str_repeat("\u{1F43E}", 500)])->assertCreated();
    }

    public function test_feed_is_bounded_newest_first_and_updates_after_deletion(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            $this->guestMessage(['body' => 'Message '.$i]);
        }
        $response = $this->getJson(route('chat.index'))->assertOk()->assertJsonCount(50, 'messages')
            ->assertJsonPath('messages.0.id', 55)->assertJsonPath('messages.49.id', 6);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        ChatMessage::findOrFail(55)->delete();
        $this->getJson(route('chat.index'))->assertJsonCount(50, 'messages')->assertJsonPath('messages.0.id', 54)
            ->assertJsonPath('messages.49.id', 5)->assertJsonMissing(['id' => 55]);
    }

    public function test_cooldown_survives_name_changes_and_expires_at_five_seconds(): void
    {
        $this->freezeTime();
        $this->withCookie('chat_guest', str_repeat('a', 64));
        $this->postJson(route('chat.store'), ['name' => 'First', 'body' => 'Hello'])->assertCreated();
        $this->travel(4)->seconds();
        $this->postJson(route('chat.store'), ['name' => 'Changed', 'body' => 'Again'])->assertStatus(429);
        $this->travel(1)->seconds();
        $this->postJson(route('chat.store'), ['name' => 'Changed', 'body' => 'Now'])->assertCreated();
    }

    public function test_lock_prevents_concurrent_posts_and_member_cooldown_ignores_guest_cookie(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);
        $key = app(ChatService::class)->cooldownKey(['user_id' => $member->id]);
        $lock = Cache::lock($key.':lock', 10);
        $this->assertTrue($lock->get());
        $this->postJson(route('chat.store'), ['body' => 'Blocked'])->assertStatus(429);
        $lock->release();
        $this->postJson(route('chat.store'), ['body' => 'Allowed'])->assertCreated();
        $this->withCookie('chat_guest', str_repeat('b', 64))->postJson(route('chat.store'), ['body' => 'Too soon'])->assertStatus(429);
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_ip_throttle_limits_guest_identity_rotation(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('chat.store'), ['body' => 'Missing name'])->assertUnprocessable();
        }
        $this->postJson(route('chat.store'), ['name' => 'Visitor', 'body' => 'Too many requests'])->assertStatus(429);
    }

    public function test_chat_polling_does_not_consume_posting_or_login_rate_limits(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        for ($i = 0; $i < 25; $i++) {
            $this->getJson(route('chat.index'))->assertOk();
        }
        $this->postJson(route('chat.store'), ['name' => 'Visitor', 'body' => 'Hello'])->assertCreated();
        $this->post(route('login'), ['email' => $member->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($member);
    }

    public function test_guests_and_members_cannot_access_any_moderation_action(): void
    {
        $message = $this->guestMessage();
        $mute = $this->guestMute();
        foreach ([null, User::factory()->create()] as $viewer) {
            if ($viewer) {
                $this->actingAs($viewer);
            }
            $status = $viewer ? 403 : 401;
            $this->getJson(route('staff.chat.index'))->assertStatus($status);
            $this->deleteJson(route('staff.chat.destroy', $message), ['reason' => 'Test reason'])->assertStatus($status);
            $this->postJson(route('staff.chat.mute', $message), ['reason' => 'Test reason', 'duration' => '1h'])->assertStatus($status);
            $this->patchJson(route('staff.chat.unmute', $mute), ['reason' => 'Test reason'])->assertStatus($status);
            $this->patchJson(route('staff.chat.settings'), ['enabled' => false])->assertStatus($status);
        }
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_moderators_can_delete_mute_deleted_messages_and_unmute_with_audit(): void
    {
        $staff = User::factory()->create(['role' => 'moderator']);
        $message = $this->guestMessage(['body' => '<img src=x onerror=alert(1)>']);
        $this->actingAs($staff)->get(route('staff.chat.index'))->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('action="'.route('staff.chat.settings').'"', false);
        $this->delete(route('staff.chat.destroy', $message), ['reason' => 'Spam message'])->assertRedirect();
        $this->assertSoftDeleted($message);
        $this->post(route('staff.chat.mute', $message), ['reason' => 'Repeated spam', 'duration' => '24h', 'user_id' => $staff->id])->assertRedirect();
        $mute = ChatMute::firstOrFail();
        $this->assertNull($mute->user_id);
        $this->assertSame($message->guest_hash, $mute->guest_hash);
        $this->assertSame(now()->addDay()->timestamp, $mute->expires_at->timestamp);
        $this->patch(route('staff.chat.unmute', $mute), ['reason' => 'Resolved now'])->assertRedirect();
        $this->assertNotNull($mute->fresh()->revoked_at);
        $this->assertSame($staff->id, $mute->fresh()->revoked_by);
        $this->assertSame(['chat.message.deleted', 'chat.participant.muted', 'chat.participant.unmuted'], AuditLog::orderBy('id')->pluck('action')->all());
        $this->assertSame(3, AuditLog::where('user_id', $staff->id)->count());
    }

    public function test_moderation_validates_reason_and_duration_and_preserves_form_handler(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $message = $this->guestMessage();
        $this->actingAs($staff)->deleteJson(route('staff.chat.destroy', $message))->assertUnprocessable();
        $this->postJson(route('staff.chat.mute', $message), ['duration' => 'invalid', 'reason' => 'Valid reason'])->assertUnprocessable();
        $this->withHeader('X-Preserve-Form', '1')->deleteJson(route('staff.chat.destroy', $message), ['reason' => 'Spam message'])
            ->assertOk()->assertJsonStructure(['redirect', 'csrf_token']);
    }

    public function test_guest_mute_matches_browser_or_ip_but_not_unrelated_guests(): void
    {
        $this->guestMute();
        $this->withCookie('chat_guest', str_repeat('a', 64))->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->postJson(route('chat.store'), ['name' => 'New name', 'body' => 'Same browser'])->assertForbidden();
        $this->withCookie('chat_guest', str_repeat('b', 64))->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson(route('chat.index'))->assertOk()->assertJsonPath('viewer.can_post', false);
        $this->postJson(route('chat.store'), ['name' => 'Different browser', 'body' => 'Shared IP'])->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.21'])
            ->postJson(route('chat.store'), ['name' => 'Unrelated', 'body' => 'Hello'])->assertCreated();
    }

    public function test_member_mute_follows_account_and_does_not_mute_other_members_on_shared_ip(): void
    {
        $member = User::factory()->create();
        ChatMute::create(['user_id' => $member->id, 'participant_name' => $member->name, 'reason' => 'Spam', 'expires_at' => null]);
        $this->actingAs($member)->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->postJson(route('chat.store'), ['body' => 'Muted'])->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson(route('chat.store'), ['body' => 'Not muted'])->assertCreated();
    }

    public function test_mute_expiry_boundary_indefinite_and_revoked_mutes(): void
    {
        $this->freezeTime();
        $mute = $this->guestMute(['expires_at' => now()->addHour()]);
        $this->withCookie('chat_guest', str_repeat('a', 64));
        $this->travel(3599)->seconds();
        $this->getJson(route('chat.index'))->assertJsonPath('viewer.can_post', false);
        $this->travel(1)->seconds();
        $this->getJson(route('chat.index'))->assertJsonPath('viewer.can_post', true);
        $mute->update(['expires_at' => null]);
        $this->travel(365)->days();
        $this->getJson(route('chat.index'))->assertJsonPath('viewer.can_post', false);
        $mute->update(['revoked_at' => now()]);
        $this->getJson(route('chat.index'))->assertJsonPath('viewer.can_post', true);
    }

    public function test_timed_mute_notice_has_readable_server_date_and_browser_expiry(): void
    {
        $this->freezeTime();
        $expiry = now()->addDay();
        $mute = $this->guestMute(['expires_at' => $expiry, 'reason' => 'Private staff reason']);
        $this->withCookie('chat_guest', str_repeat('a', 64))->getJson(route('chat.index'))
            ->assertOk()
            ->assertJsonPath('viewer.muted_until', $expiry->toIso8601String())
            ->assertJsonPath('viewer.message', 'You are muted from chat until '.$expiry->format('F j, Y \a\t g:i A T').'.')
            ->assertDontSee('Private staff reason')
            ->assertDontSee($mute->guest_hash);
        $mute->update(['expires_at' => null]);
        $this->getJson(route('chat.index'))->assertJsonPath('viewer.muted_until', null)
            ->assertJsonPath('viewer.message', __('chat.muted'));
    }

    public function test_staff_history_shows_actions_reasons_and_actors_even_after_deletion_and_unmute(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'name' => 'History Moderator']);
        $message = $this->guestMessage();
        $this->actingAs($staff)->delete(route('staff.chat.destroy', $message), ['reason' => 'Deletion reason <script>unsafe()</script>'])->assertRedirect();
        $this->post(route('staff.chat.mute', $message), ['reason' => 'Mute reason for spam', 'duration' => '7d'])->assertRedirect();
        $mute = ChatMute::firstOrFail();
        $this->patch(route('staff.chat.unmute', $mute), ['reason' => 'Appeal accepted'])->assertRedirect();
        $this->patch(route('staff.chat.settings'), ['enabled' => false])->assertRedirect();
        AuditLog::create(['user_id' => $staff->id, 'action' => 'theme.updated', 'changes' => ['reason' => 'Unrelated theme reason']]);

        $response = $this->get(route('staff.chat.index'))->assertOk()
            ->assertSee('Moderation history')
            ->assertSeeInOrder(['Guest posting changed', 'Mute lifted', 'Participant muted', 'Message deleted'])
            ->assertSee('History Moderator')->assertSee('Visitor')
            ->assertSee('Appeal accepted')->assertSee('Mute reason for spam')
            ->assertSee('Deletion reason &lt;script&gt;unsafe()&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe()</script>', false)
            ->assertDontSee('Unrelated theme reason')->assertDontSee($message->guest_hash)
            ->assertDontSee($message->ip_hash);
        $this->assertSame(4, $response->viewData('history')->total());
        $this->assertSame(0, $response->viewData('mutes')->total());
    }

    public function test_legacy_chat_history_is_paginated_and_resolves_soft_deleted_targets(): void
    {
        $staff = User::factory()->create(['role' => 'moderator']);
        $message = $this->guestMessage(['author_name' => 'Historical Visitor']);
        $message->delete();
        for ($i = 1; $i <= 23; $i++) {
            AuditLog::create([
                'user_id' => $staff->id, 'action' => 'chat.message.deleted',
                'auditable_type' => ChatMessage::class, 'auditable_id' => $message->id,
                'changes' => ['reason' => 'Legacy reason '.$i],
            ]);
        }
        $response = $this->actingAs($staff)->get(route('staff.chat.index'))->assertOk()->assertSee('Historical Visitor');
        $this->assertSame(23, $response->viewData('history')->total());
        $this->assertCount(20, $response->viewData('history'));
        $this->assertSame('Legacy reason 23', $response->viewData('history')->first()->changes['reason']);
        $response = $this->get(route('staff.chat.index', ['history_page' => 2]))->assertOk();
        $this->assertCount(3, $response->viewData('history'));
        $this->assertSame('Legacy reason 3', $response->viewData('history')->first()->changes['reason']);
        $message->forceDelete();
        $this->get(route('staff.chat.index'))
            ->assertOk()->assertSee('No longer available');
    }

    public function test_all_mute_durations_are_supported(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $message = $this->guestMessage();
        foreach (['1h' => 3600, '24h' => 86400, '7d' => 604800, 'forever' => null] as $duration => $seconds) {
            $this->post(route('staff.chat.mute', $message), ['reason' => 'Spam messages', 'duration' => $duration])->assertRedirect();
            $this->assertSame($seconds ? now()->timestamp + $seconds : null, ChatMute::latest('id')->firstOrFail()->expires_at?->timestamp);
        }
    }

    public function test_only_admin_can_toggle_guests_and_members_can_still_post(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($moderator)->patchJson(route('staff.chat.settings'), ['enabled' => false])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('staff.chat.index'))->assertOk()->assertSee('Pause guest posting');
        $this->patch(route('staff.chat.settings'), ['enabled' => false])->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'chat_guest_posting', 'value' => '0']);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'chat.settings.updated']);
        $this->app['auth']->forgetGuards();
        $this->getJson(route('chat.index'))->assertOk()->assertJsonPath('viewer.can_post', false);
        $this->postJson(route('chat.store'), ['name' => 'Visitor', 'body' => 'Blocked'])->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson(route('chat.store'), ['body' => 'Member allowed'])->assertCreated();
        $this->assertDatabaseHas('site_settings', ['key' => 'theme']);
    }

    public function test_sidebar_placement_and_staff_navigation(): void
    {
        $html = $this->get(route('home'))->assertOk()->assertSee('data-chat-form', false)
            ->assertDontSee('Public chat. Keep it friendly.')
            ->assertDontSee('Updates every 10 seconds.')
            ->assertDontSee('chatbox-hint')
            ->getContent();
        $this->assertLessThan(strpos($html, 'data-chatbox'), strpos($html, 'clan-list'));
        $this->assertLessThan(strpos($html, '<main class="main-content">'), strpos($html, 'data-chatbox'));
        $this->actingAs(User::factory()->create(['role' => 'moderator']))->get(route('staff.index'))
            ->assertOk()->assertSee(route('staff.chat.index'))->assertDontSee('data-chatbox', false);
    }

    private function guestMessage(array $attributes = []): ChatMessage
    {
        $chat = app(ChatService::class);

        return ChatMessage::create([
            'is_guest' => true, 'author_name' => 'Visitor',
            'guest_hash' => $chat->hash('guest:'.str_repeat('a', 64)),
            'ip_hash' => $chat->hash('ip:127.0.0.1'), 'body' => 'Hello',
            ...$attributes,
        ]);
    }

    private function guestMute(array $attributes = []): ChatMute
    {
        $message = $this->guestMessage();

        return ChatMute::create([
            'participant_name' => 'Visitor', 'guest_hash' => $message->guest_hash,
            'ip_hash' => $message->ip_hash, 'reason' => 'Spam', ...$attributes,
        ]);
    }
}
