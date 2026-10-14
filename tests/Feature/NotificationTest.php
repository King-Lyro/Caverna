<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_mating_notifications_open_the_decision_section(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        $member->notify(new \App\Notifications\SiteNotification([
            'title' => 'Mating request', 'message' => 'You received a mating request.',
            'url' => route('characters'),
        ]));
        $notification = $member->notifications()->firstOrFail();
        $this->actingAs($member)->get(route('notifications'))->assertOk()
            ->assertSee('Breeding request')->assertDontSee('Mating request')->assertSee('Open update');
        $this->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard').'#breeding-requests');
    }
    public function test_member_can_view_and_mark_notifications_read(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $user->notify(new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toDatabase($notifiable): array
            {
                return ['title' => 'Test update', 'message' => 'A story changed.'];
            }
        });
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->get(route('notifications'))->assertOk()->assertSee('Test update');
        $this->actingAs($user)->patch(route('notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
