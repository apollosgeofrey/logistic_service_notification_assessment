<?php

namespace Tests\Feature;

use App\Exceptions\TransientProviderException;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Subscriber;
use App\Services\NotificationProviderResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_send_creates_queued_notifications_and_dispatches_jobs(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'alice@example.com',
            'phone' => null,
        ]);

        $response = $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Hello Alice',
            'priority' => 'critical',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'bulk-send-test-1',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('idempotency_key', 'bulk-send-test-1')
            ->assertJsonPath('notifications.0.status', Notification::STATUS_QUEUED)
            ->assertJsonPath('notifications.0.priority', Notification::PRIORITY_CRITICAL);

        $this->assertDatabaseHas('notifications', [
            'subscriber_id' => $subscriber->id,
            'status' => Notification::STATUS_QUEUED,
            'idempotency_key' => 'bulk-send-test-1',
        ]);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($subscriber): bool {
            return $job->notification->subscriber_id === $subscriber->id
                && $job->queue === 'notifications.critical';
        });
    }

    public function test_marketing_jobs_use_marketing_queue(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'marketer@example.com',
            'phone' => null,
        ]);

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Summer sale',
            'priority' => 'marketing',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'bulk-send-marketing-queue',
        ])->assertAccepted();

        Queue::assertPushed(SendNotificationJob::class, fn (SendNotificationJob $job): bool => $job->queue === 'notifications.marketing');
    }

    public function test_default_jobs_use_default_queue(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'user@example.com',
            'phone' => null,
        ]);

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Order update',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'bulk-send-default-queue',
        ])->assertAccepted();

        Queue::assertPushed(SendNotificationJob::class, fn (SendNotificationJob $job): bool => $job->queue === 'notifications.default');
    }

    public function test_idempotent_bulk_send_does_not_create_duplicates(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'bob@example.com',
            'phone' => null,
        ]);

        $payload = [
            'channel' => 'email',
            'content' => 'Promo offer',
            'priority' => 'marketing',
            'subscriber_ids' => [$subscriber->id],
        ];

        $first = $this->postJson('/api/v1/notifications/bulk-send', $payload, [
            'Idempotency-Key' => 'bulk-send-test-2',
        ]);

        $second = $this->postJson('/api/v1/notifications/bulk-send', $payload, [
            'Idempotency-Key' => 'bulk-send-test-2',
        ]);

        $first->assertAccepted();
        $second->assertAccepted();
        $this->assertSame(
            $first->json('notifications.0.id'),
            $second->json('notifications.0.id')
        );
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    public function test_idempotency_conflict_returns_409_for_different_payload(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'conflict@example.com',
            'phone' => null,
        ]);

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Original message',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'conflict-key',
        ])->assertAccepted();

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Different message',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'conflict-key',
        ])
            ->assertConflict()
            ->assertJsonPath('message', 'Idempotency key was already used with a different request payload.');

        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    public function test_end_to_end_bulk_send_delivers_notification_with_sync_queue(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => 'e2e@example.com',
            'phone' => null,
        ]);

        $response = $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'End to end message',
            'priority' => 'critical',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'bulk-send-e2e',
        ]);

        $response->assertAccepted();

        $notificationId = $response->json('notifications.0.id');

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'status' => Notification::STATUS_DELIVERED,
        ]);

        $this->getJson("/api/v1/notifications/{$notificationId}")
            ->assertOk()
            ->assertJsonPath('status', Notification::STATUS_DELIVERED)
            ->assertJsonPath('external_id', fn ($value): bool => is_string($value) && $value !== '');
    }

    public function test_sms_bulk_send_delivers_notification_end_to_end(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => null,
            'phone' => '+15550001111',
        ]);

        $response = $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'sms',
            'content' => 'Your code is 9999',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'bulk-send-sms-e2e',
        ]);

        $response->assertAccepted();

        $this->assertDatabaseHas('notifications', [
            'id' => $response->json('notifications.0.id'),
            'channel' => Notification::CHANNEL_SMS,
            'status' => Notification::STATUS_DELIVERED,
        ]);
    }

    public function test_job_transitions_notification_to_delivered(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => 'carol@example.com',
            'phone' => null,
        ]);

        $notification = Notification::factory()->create([
            'subscriber_id' => $subscriber->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'status' => Notification::STATUS_QUEUED,
        ]);

        (new SendNotificationJob($notification))->handle(app(NotificationProviderResolver::class));

        $notification->refresh();

        $this->assertSame(Notification::STATUS_DELIVERED, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertNotNull($notification->delivered_at);
        $this->assertNotNull($notification->external_id);
    }

    public function test_transient_failure_resets_notification_to_queued(): void
    {
        Config::set('notifications.mock.force_transient_failure', true);

        $subscriber = Subscriber::factory()->create([
            'email' => 'retry@example.com',
            'phone' => null,
        ]);

        $notification = Notification::factory()->create([
            'subscriber_id' => $subscriber->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'status' => Notification::STATUS_QUEUED,
        ]);

        $job = new SendNotificationJob($notification);

        try {
            $job->handle(app(NotificationProviderResolver::class));
            $this->fail('Expected TransientProviderException was not thrown.');
        } catch (TransientProviderException) {
            // expected
        }

        $notification->refresh();

        $this->assertSame(Notification::STATUS_QUEUED, $notification->status);
        $this->assertNull($notification->sent_at);
    }

    public function test_permanent_failure_marks_notification_as_discarded(): void
    {
        Config::set('notifications.mock.force_permanent_failure', true);

        $subscriber = Subscriber::factory()->create([
            'email' => 'bad@example.com',
            'phone' => null,
        ]);

        $notification = Notification::factory()->create([
            'subscriber_id' => $subscriber->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'status' => Notification::STATUS_QUEUED,
        ]);

        (new SendNotificationJob($notification))->handle(app(NotificationProviderResolver::class));

        $notification->refresh();

        $this->assertSame(Notification::STATUS_DISCARDED, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertNull($notification->delivered_at);
        $this->assertNotNull($notification->failure_reason);
    }

    public function test_failed_callback_marks_notification_as_discarded(): void
    {
        $notification = Notification::factory()->create([
            'status' => Notification::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $job = new SendNotificationJob($notification);
        $job->failed(new TransientProviderException('Gateway timeout'));

        $notification->refresh();

        $this->assertSame(Notification::STATUS_DISCARDED, $notification->status);
        $this->assertSame('Gateway timeout', $notification->failure_reason);
    }

    public function test_job_skips_already_processed_notification(): void
    {
        $notification = Notification::factory()->delivered()->create();

        (new SendNotificationJob($notification))->handle(app(NotificationProviderResolver::class));

        $notification->refresh();

        $this->assertSame(Notification::STATUS_DELIVERED, $notification->status);
    }

    public function test_notification_status_endpoint_returns_notification(): void
    {
        $notification = Notification::factory()->delivered()->create();

        $response = $this->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJsonPath('id', $notification->id)
            ->assertJsonPath('status', Notification::STATUS_DELIVERED);
    }

    public function test_notification_status_endpoint_returns_404_when_missing(): void
    {
        $this->getJson('/api/v1/notifications/99999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Notification not found.');
    }

    public function test_subscriber_notifications_endpoint_returns_history(): void
    {
        $subscriber = Subscriber::factory()->create();
        Notification::factory()->count(2)->create([
            'subscriber_id' => $subscriber->id,
        ]);

        $response = $this->getJson("/api/v1/subscribers/{$subscriber->id}/notifications");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_subscriber_notifications_can_filter_by_status_and_channel(): void
    {
        $subscriber = Subscriber::factory()->create();

        Notification::factory()->delivered()->create([
            'subscriber_id' => $subscriber->id,
            'channel' => Notification::CHANNEL_EMAIL,
        ]);

        Notification::factory()->create([
            'subscriber_id' => $subscriber->id,
            'channel' => Notification::CHANNEL_SMS,
            'status' => Notification::STATUS_QUEUED,
        ]);

        $this->getJson("/api/v1/subscribers/{$subscriber->id}/notifications?status=delivered&channel=email")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', Notification::STATUS_DELIVERED)
            ->assertJsonPath('data.0.channel', Notification::CHANNEL_EMAIL);
    }

    public function test_subscriber_notifications_endpoint_returns_404_when_missing(): void
    {
        $this->getJson('/api/v1/subscribers/99999/notifications')
            ->assertNotFound()
            ->assertJsonPath('message', 'Subscriber not found.');
    }

    public function test_idempotency_replay_works_after_cache_expires(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'cache@example.com',
            'phone' => null,
        ]);

        $payload = [
            'channel' => 'email',
            'content' => 'Cache expiry replay',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ];

        $first = $this->postJson('/api/v1/notifications/bulk-send', $payload, [
            'Idempotency-Key' => 'cache-expiry-key',
        ])->assertAccepted();

        Cache::flush();

        $second = $this->postJson('/api/v1/notifications/bulk-send', $payload, [
            'Idempotency-Key' => 'cache-expiry-key',
        ])->assertAccepted();

        $this->assertSame($first->json('notifications.0.id'), $second->json('notifications.0.id'));
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    public function test_idempotency_conflict_after_cache_expires_returns_409(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->create([
            'email' => 'conflict-cache@example.com',
            'phone' => null,
        ]);

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Original after cache flush',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'cache-conflict-key',
        ])->assertAccepted();

        Cache::flush();

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Different after cache flush',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'cache-conflict-key',
        ])
            ->assertConflict();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_bulk_send_requires_idempotency_key_header(): void
    {
        $subscriber = Subscriber::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'Missing key',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The Idempotency-Key header is required.');
    }

    public function test_bulk_send_rejects_subscriber_without_required_contact(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => null,
            'phone' => '+15550002222',
        ]);

        $this->postJson('/api/v1/notifications/bulk-send', [
            'channel' => 'email',
            'content' => 'No email on file',
            'priority' => 'default',
            'subscriber_ids' => [$subscriber->id],
        ], [
            'Idempotency-Key' => 'missing-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscriber_ids.0']);
    }

    protected function tearDown(): void
    {
        Config::set('notifications.mock.force_transient_failure', false);
        Config::set('notifications.mock.force_permanent_failure', false);
        Cache::flush();

        parent::tearDown();
    }
}
