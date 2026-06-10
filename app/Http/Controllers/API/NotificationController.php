<?php

namespace App\Http\Controllers\API;

use App\Exceptions\IdempotencyConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkSendNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\IdempotencyService;
use App\Services\NotificationService;
use App\Services\NotificationStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly NotificationService $notificationService,
        private readonly NotificationStatusService $notificationStatusService,
    ) {}

    public function bulkSend(BulkSendNotificationRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json([
                'message' => 'The Idempotency-Key header is required.',
            ], 422);
        }

        try {
            $response = $this->idempotencyService->process(
                $idempotencyKey,
                $request->idempotencyPayload(),
                function () use ($request, $idempotencyKey) {
                    $notifications = $this->notificationService->bulkSend(
                        $idempotencyKey,
                        $request->validated()
                    );

                    return [
                        'idempotency_key' => $idempotencyKey,
                        'batch_id' => $notifications->first()->batch_id,
                        'notifications' => NotificationResource::collection($notifications)->resolve(),
                    ];
                }
            );
        } catch (IdempotencyConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json($response, 202);
    }

    public function notificationStatus(int $id): NotificationResource|JsonResponse
    {
        $notification = $this->notificationStatusService->find($id);

        if (! $notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        return new NotificationResource($notification);
    }

    public function subscriberNotifications(Request $request, int $id): AnonymousResourceCollection|JsonResponse
    {
        $notifications = $this->notificationStatusService->forSubscriber($id, [
            'status' => $request->query('status'),
            'channel' => $request->query('channel'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        if ($notifications === null) {
            return response()->json([
                'message' => 'Subscriber not found.',
            ], 404);
        }

        return NotificationResource::collection($notifications);
    }
}
