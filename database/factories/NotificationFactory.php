<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscriber_id' => Subscriber::factory(),
            'batch_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'channel' => fake()->randomElement([Notification::CHANNEL_EMAIL, Notification::CHANNEL_SMS]),
            'content' => fake()->sentence(),
            'priority' => Notification::PRIORITY_DEFAULT,
            'status' => Notification::STATUS_QUEUED,
            'sent_at' => null,
            'delivered_at' => null,
            'external_id' => null,
            'failure_reason' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Notification::PRIORITY_CRITICAL,
        ]);
    }

    public function marketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Notification::PRIORITY_MARKETING,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Notification::STATUS_DELIVERED,
            'sent_at' => now()->subMinute(),
            'delivered_at' => now(),
            'external_id' => (string) Str::uuid(),
        ]);
    }
}
