<?php

namespace App\Services\Providers;

use App\Contracts\NotificationProviderInterface;
use App\Exceptions\TransientProviderException;
use App\Models\Notification;
use Illuminate\Support\Str;

class MockEmailProvider implements NotificationProviderInterface
{
    public function send(Notification $notification): ProviderResult
    {
        if (config('notifications.mock.force_transient_failure')) {
            throw new TransientProviderException('Email gateway temporarily unavailable');
        }

        if (config('notifications.mock.force_permanent_failure')) {
            return ProviderResult::permanentFailure(
                config('notifications.mock.permanent_failure_reason')
            );
        }

        if (empty($notification->subscriber?->email)) {
            return ProviderResult::permanentFailure('Subscriber has no email address');
        }

        return ProviderResult::success('email-'.Str::uuid()->toString());
    }
}
