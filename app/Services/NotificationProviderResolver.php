<?php

namespace App\Services;

use App\Contracts\NotificationProviderInterface;
use App\Models\Notification;
use App\Services\Providers\MockEmailProvider;
use App\Services\Providers\MockSmsProvider;
use InvalidArgumentException;

class NotificationProviderResolver
{
    public function resolve(string $channel): NotificationProviderInterface
    {
        return match ($channel) {
            Notification::CHANNEL_EMAIL => app(MockEmailProvider::class),
            Notification::CHANNEL_SMS => app(MockSmsProvider::class),
            default => throw new InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }
}
