<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        Log::info('Notification handed off to provider', [
            'notification_id' => $event->notification->id,
            'subscriber_id' => $event->notification->subscriber_id,
            'channel' => $event->notification->channel,
            'priority' => $event->notification->priority,
        ]);
    }
}
