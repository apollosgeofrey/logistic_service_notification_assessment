<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Subscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationStatusService
{
    public function find(int $id): ?Notification
    {
        return Notification::query()
            ->with('subscriber')
            ->find($id);
    }

    public function forSubscriber(int $subscriberId, array $filters = []): ?LengthAwarePaginator
    {
        if (! Subscriber::query()->whereKey($subscriberId)->exists()) {
            return null;
        }

        $query = Notification::query()
            ->where('subscriber_id', $subscriberId)
            ->with('subscriber')
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
