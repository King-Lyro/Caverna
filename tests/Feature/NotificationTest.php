<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

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
