<?php

declare(strict_types=1);

namespace App\Feature\Payment\Webhook;

interface WebhookVerifierInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function verify(array $payload): WebhookVerificationResult;
}
