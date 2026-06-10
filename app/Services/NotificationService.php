<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    public function bulkSend(string $idempotencyKey, array $data): Collection
    {
        $batchId = (string) Str::uuid();
        $subscribers = Subscriber::query()
            ->whereIn('id', $data['subscriber_ids'])
            ->get()
            ->keyBy('id');

        $notifications = collect();

        foreach ($data['subscriber_ids'] as $subscriberId) {
            $notification = Notification::create([
                'subscriber_id' => $subscriberId,
                'batch_id' => $batchId,
                'idempotency_key' => $idempotencyKey,
                'channel' => $data['channel'],
                'content' => $data['content'],
                'priority' => $data['priority'],
                'status' => Notification::STATUS_QUEUED,
            ]);

            SendNotificationJob::dispatch($notification)
                ->onQueue($this->queueForPriority($data['priority']));

            $notification->setRelation('subscriber', $subscribers->get($subscriberId));
            $notifications->push($notification);
        }

        return $notifications;
    }

    public function queueForPriority(string $priority): string
    {
        return match ($priority) {
            Notification::PRIORITY_CRITICAL => config('notifications.queues.critical'),
            Notification::PRIORITY_MARKETING => config('notifications.queues.marketing'),
            default => config('notifications.queues.default'),
        };
    }
}
