<?php

declare(strict_types=1);

namespace App\Feature\Payment\Webhook;

final readonly class WebhookVerificationResult
{
    public function __construct(
        public bool $isValid,
        public ?string $publicId = null,
        public ?string $providerEventId = null,
        public ?string $reason = null,
    ) {
    }

    public static function invalid(string $reason): self
    {
        return new self(false, reason: $reason);
    }
}
