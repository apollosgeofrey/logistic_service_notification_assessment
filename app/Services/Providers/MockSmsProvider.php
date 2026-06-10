<?php

namespace App\Services\Providers;

use App\Contracts\NotificationProviderInterface;
use App\Exceptions\TransientProviderException;
use App\Models\Notification;
use Illuminate\Support\Str;

class MockSmsProvider implements NotificationProviderInterface
{
    public function send(Notification $notification): ProviderResult
    {
        if (config('notifications.mock.force_transient_failure')) {
            throw new TransientProviderException('SMS gateway temporarily unavailable');
        }

        if (config('notifications.mock.force_permanent_failure')) {
            return ProviderResult::permanentFailure(
                config('notifications.mock.permanent_failure_reason')
            );
        }

        if (empty($notification->subscriber?->phone)) {
            return ProviderResult::permanentFailure('Subscriber has no phone number');
        }

        return ProviderResult::success('sms-'.Str::uuid()->toString());
    }
}
