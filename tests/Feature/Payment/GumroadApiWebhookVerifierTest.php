<?php

declare(strict_types=1);

namespace App\Tests\Feature\Payment;

use App\Feature\Payment\Webhook\GumroadApiWebhookVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GumroadApiWebhookVerifierTest extends TestCase
{
    public function testFailsClosedWhenProviderCredentialsAreMissing(): void
    {
        $verifier = new GumroadApiWebhookVerifier(new MockHttpClient(), new NullLogger(), null, null);

        $result = $verifier->verify([
            'sale_id' => 'sale-1',
            'url_params' => ['custom' => 'CMR-12345678'],
        ]);

        self::assertFalse($result->isValid);
        self::assertSame('provider_verification_not_configured', $result->reason);
    }

    public function testAcceptsSaleVerifiedByGumroadApi(): void
    {
        $verifier = new GumroadApiWebhookVerifier(
            new MockHttpClient(new MockResponse('{"success":true,"sale":{"seller_id":"seller-1","refunded":false,"disputed":false}}')),
            new NullLogger(),
            'seller-1',
            'token-1',
        );

        $result = $verifier->verify([
            'sale_id' => 'sale-1',
            'url_params' => ['custom' => 'CMR-12345678'],
        ]);

        self::assertTrue($result->isValid);
        self::assertSame('CMR-12345678', $result->publicId);
        self::assertSame('sale-1', $result->providerEventId);
    }

    public function testRejectsSellerMismatch(): void
    {
        $verifier = new GumroadApiWebhookVerifier(
            new MockHttpClient(new MockResponse('{"success":true,"sale":{"seller_id":"other","refunded":false,"disputed":false}}')),
            new NullLogger(),
            'seller-1',
            'token-1',
        );

        self::assertFalse($verifier->verify([
            'sale_id' => 'sale-1',
            'url_params' => ['custom' => 'CMR-12345678'],
        ])->isValid);
    }
}
