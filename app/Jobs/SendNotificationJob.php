<?php

namespace App\Jobs;

use App\Events\NotificationSent;
use App\Exceptions\TransientProviderException;
use App\Models\Notification;
use App\Services\NotificationProviderResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Notification $notification) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(NotificationProviderResolver $providerResolver): void
    {
        $notification = $this->notification->fresh(['subscriber']);

        if (! $notification || $notification->status !== Notification::STATUS_QUEUED) {
            return;
        }

        $notification->update([
            'status' => Notification::STATUS_SENT,
            'sent_at' => now(),
        ]);

        event(new NotificationSent($notification));

        try {
            $result = $providerResolver->resolve($notification->channel)->send($notification);
        } catch (TransientProviderException $exception) {
            $this->resetForRetry($notification);

            throw $exception;
        }

        if ($result->transientFailure) {
            $this->resetForRetry($notification);

            throw new TransientProviderException($result->failureReason ?? 'Transient provider failure');
        }

        if (! $result->delivered) {
            $notification->update([
                'status' => Notification::STATUS_DISCARDED,
                'failure_reason' => $result->failureReason,
            ]);

            return;
        }

        $notification->update([
            'status' => Notification::STATUS_DELIVERED,
            'delivered_at' => now(),
            'external_id' => $result->externalId,
            'failure_reason' => null,
        ]);
    }

    private function resetForRetry(Notification $notification): void
    {
        $notification->update([
            'status' => Notification::STATUS_QUEUED,
            'sent_at' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $notification = $this->notification->fresh();

        if (! $notification || $notification->status === Notification::STATUS_DELIVERED) {
            return;
        }

        $notification->update([
            'status' => Notification::STATUS_DISCARDED,
            'failure_reason' => $exception?->getMessage() ?? 'Notification delivery failed after retries',
        ]);
    }
}
