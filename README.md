# Notification Service

Laravel-based microservice for bulk SMS and Email notifications with priority queuing, delivery status tracking, idempotency, and automatic retries.

**Author:** Apollos Geofrey

## Features

- **Bulk send API** — one request creates notifications for multiple subscribers
- **Priority traffic** — `critical` messages use a dedicated high-priority queue processed before `default` and `marketing`
- **Delivery statuses** — `queued`, `sent`, `delivered`, `discarded`
- **Reliability** — persistent Redis queues, at-least-once delivery, 3 retries with exponential backoff
- **Idempotency** — `Idempotency-Key` header prevents duplicate sends on retried client requests
- **Mock providers** — stub SMS/Email gateways for local and integration testing
- **Docker Compose** — PostgreSQL, Redis, API, and queue worker start with one command

## Tech Stack

| Component | Technology |
|-----------|------------|
| Framework | PHP 8.3, Laravel 13 |
| Database | PostgreSQL 15 |
| Message queue | Redis (Laravel queues) |
| Cache / idempotency | Redis |
| Broker (optional) | RabbitMQ container included for stack alignment |
| Tests | PHPUnit integration tests |

## Quick Start (Docker)

### Prerequisites

- Docker and Docker Compose
- Ports available: `8000`, `5432`, `6379`, `5672`, `15672`

### 1. Clone and configure

```bash
git clone <your-repo-url>
cd logistic_service_notification_assessment
cp .env.example .env
php artisan key:generate   # only needed for local (non-Docker) runs
```

### 2. Start all services

```bash
docker compose up --build
```

This starts:

| Service | URL / Port | Purpose |
|---------|------------|---------|
| `app` | http://localhost:8000 | REST API |
| `worker` | — | Processes notification queues |
| `pgsql` | localhost:5432 | PostgreSQL |
| `redis` | localhost:6379 | Queue + cache |
| `rabbitmq` | http://localhost:15672 | Management UI (guest/guest) |

On first boot the entrypoint runs `composer install`, `migrate`, and `db:seed`.

### 3. Seed subscribers (if needed again)

```bash
docker compose exec app php artisan db:seed
```

Demo subscribers:

| ID | Name | Email | Phone |
|----|------|-------|-------|
| 1 | Alice Email | alice@example.com | — |
| 2 | Bob SMS | — | +15551234567 |
| 3 | Carol Both | carol@example.com | +15559876543 |

## Local Development (without Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Ensure PostgreSQL and Redis are running, then start the app and worker in separate terminals:

```bash
php artisan serve
php artisan queue:work redis --queue=notifications.critical,notifications.default,notifications.marketing --tries=3
```

Or use the bundled dev script:

```bash
composer dev
```

## API Documentation

- **Postman collection:** [postman/notification-service.postman_collection.json](postman/notification-service.postman_collection.json)

Import the collection into Postman: **File → Import → select the JSON file**.

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/v1/notifications/bulk-send` | Start bulk send (requires `Idempotency-Key` header) |
| `GET` | `/api/v1/notifications/{id}` | Get notification status |
| `GET` | `/api/v1/subscribers/{id}/notifications` | Subscriber history (`?status=`, `?channel=`, `?per_page=`) |

### Example: bulk send

```bash
curl -X POST http://localhost:8000/api/v1/notifications/bulk-send \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: demo-key-001" \
  -d '{
    "channel": "email",
    "content": "Your verification code is 1234",
    "priority": "critical",
    "subscriber_ids": [1]
  }'
```

**Response `202 Accepted`:**

```json
{
  "idempotency_key": "demo-key-001",
  "batch_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "notifications": [
    {
      "id": 1,
      "subscriber_id": 1,
      "status": "queued",
      "priority": "critical",
      "channel": "email"
    }
  ]
}
```

### Example: check status

```bash
curl http://localhost:8000/api/v1/notifications/1
```

### Example: subscriber history

```bash
curl "http://localhost:8000/api/v1/subscribers/1/notifications?status=delivered"
```

## Priority Queues

Jobs are routed by priority:

| Priority | Queue name |
|----------|------------|
| `critical` | `notifications.critical` |
| `default` | `notifications.default` |
| `marketing` | `notifications.marketing` |

The worker processes queues in that order so critical messages are not blocked by marketing campaigns.

## Notification Lifecycle

```
queued → sent → delivered
              ↘ discarded (permanent failure or retries exhausted)
```

Transient gateway failures reset the notification to `queued` and trigger a retry (up to 3 attempts, backoff 10s / 30s / 60s).

## Idempotency

Send the same `Idempotency-Key` header to safely retry a bulk-send request. The service returns the original response without creating duplicate notifications.

Reusing a key with a **different** request body returns `409 Conflict`.

## Testing

```bash
php artisan test
```

Or inside Docker:

```bash
docker compose exec app php artisan test
```

The test suite includes 19 integration tests covering bulk send, priority routing, idempotency, E2E delivery, retries, failures, and all query endpoints.

## Configuration

Key environment variables (see `.env.example`):

| Variable | Default | Purpose |
|----------|---------|---------|
| `QUEUE_CONNECTION` | `redis` | Queue driver |
| `CACHE_STORE` | `redis` | Idempotency cache |
| `REDIS_CLIENT` | `predis` | Redis client (no PHP extension required) |
| `IDEMPOTENCY_TTL` | `86400` | Idempotency cache TTL (seconds) |
| `MOCK_FORCE_TRANSIENT_FAILURE` | `false` | Simulate gateway timeout in tests |
| `MOCK_FORCE_PERMANENT_FAILURE` | `false` | Simulate permanent delivery failure |

## Project Structure

```
app/
  Http/Controllers/API/NotificationController.php
  Http/Requests/BulkSendNotificationRequest.php
  Jobs/SendNotificationJob.php
  Services/IdempotencyService.php
  Services/NotificationService.php
  Services/Providers/MockEmailProvider.php
  Services/Providers/MockSmsProvider.php
postman/notification-service.postman_collection.json
docker-compose.yml
Dockerfile
tests/Feature/NotificationTest.php
```

## Health Check

```bash
curl http://localhost:8000/up
```

## Docker build troubleshooting

If `docker compose up --build` fails with `TLS handshake timeout` when pulling from Docker Hub:

1. **Pre-pull the PHP image** when your connection is stable (only one image is required now):
   ```bash
   docker pull php:8.3-cli
   docker compose up --build
   ```

2. **Retry** after restarting Docker: `sudo systemctl restart docker`

3. **Try a VPN** or different network if Docker Hub is slow or blocked in your region.

4. **Run locally without Docker** until the image pull succeeds:
   ```bash
   composer install
   php artisan migrate && php artisan db:seed
   php artisan serve
   # second terminal:
   php artisan queue:work redis --queue=notifications.critical,notifications.default,notifications.marketing --tries=3
   ```

## License

MIT
