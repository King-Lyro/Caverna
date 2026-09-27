<?php

namespace App\Notifications;

use App\Models\AdoptionListing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AdoptionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AdoptionListing $listing, private readonly string $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage(['title' => 'Adoption update', 'message' => $this->message, 'listing_id' => $this->listing->id]);
    }
}
