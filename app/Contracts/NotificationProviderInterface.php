<?php

namespace App\Contracts;

use App\Models\Notification;
use App\Services\Providers\ProviderResult;

interface NotificationProviderInterface
{
    public function send(Notification $notification): ProviderResult;
}
