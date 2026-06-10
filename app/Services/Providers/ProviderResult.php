<?php

namespace App\Services\Providers;

readonly class ProviderResult
{
    public function __construct(
        public bool $delivered,
        public ?string $externalId = null,
        public ?string $failureReason = null,
        public bool $transientFailure = false,
    ) {}

    public static function success(string $externalId): self
    {
        return new self(delivered: true, externalId: $externalId);
    }

    public static function permanentFailure(string $reason): self
    {
        return new self(delivered: false, failureReason: $reason);
    }

    public static function transientFailure(string $reason): self
    {
        return new self(delivered: false, failureReason: $reason, transientFailure: true);
    }
}
