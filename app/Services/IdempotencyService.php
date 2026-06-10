<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    public function process(string $key, array $payload, callable $callback): array
    {
        $payloadHash = $this->hashPayload($payload);
        $cacheKey = "idempotency:{$key}";

        if ($cached = Cache::get($cacheKey)) {
            $this->assertMatchingPayload($cached['payload_hash'], $payloadHash);

            return $cached['response'];
        }

        $existing = Notification::query()
            ->where('idempotency_key', $key)
            ->with('subscriber')
            ->orderBy('id')
            ->get();

        if ($existing->isNotEmpty()) {
            $this->assertMatchingPayload(
                $this->hashPayload($this->payloadFromNotifications($existing)),
                $payloadHash
            );

            $response = $this->formatResponse($key, $existing);
            $this->storeResponse($cacheKey, $payloadHash, $response);

            return $response;
        }

        $response = $callback();
        $this->storeResponse($cacheKey, $payloadHash, $response);

        return $response;
    }

    private function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function assertMatchingPayload(string $cachedHash, string $payloadHash): void
    {
        if ($cachedHash !== $payloadHash) {
            throw new IdempotencyConflictException;
        }
    }

    private function storeResponse(string $cacheKey, string $payloadHash, array $response): void
    {
        Cache::put($cacheKey, [
            'payload_hash' => $payloadHash,
            'response' => $response,
        ], config('notifications.idempotency_ttl'));
    }

    /**
     * @param  Collection<int, Notification>  $notifications
     * @return array<string, mixed>
     */
    private function payloadFromNotifications(Collection $notifications): array
    {
        $first = $notifications->first();

        return [
            'channel' => $first->channel,
            'content' => $first->content,
            'priority' => $first->priority,
            'subscriber_ids' => $notifications->pluck('subscriber_id')->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Notification>  $notifications
     */
    private function formatResponse(string $key, Collection $notifications): array
    {
        return [
            'idempotency_key' => $key,
            'batch_id' => $notifications->first()->batch_id,
            'notifications' => NotificationResource::collection($notifications)->resolve(),
        ];
    }
}
