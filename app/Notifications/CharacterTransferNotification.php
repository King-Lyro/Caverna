<?php

namespace App\Notifications;

use App\Models\Character;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CharacterTransferNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Character $character, private readonly string $action) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage(['title' => 'Character transfer update', 'message' => $this->action.' '.$this->character->name.'.', 'character_id' => $this->character->id]);
    }
}
