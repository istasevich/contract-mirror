<?php

declare(strict_types=1);

namespace App\Tests\AI;

use App\AI\Client\OpenAiLlmClient;
use App\Shared\Exception\ExternalServiceException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiLlmClientTest extends TestCase
{
    public function testGeneratesStructuredJson(): void
    {
        $client = new OpenAiLlmClient(
            client: new MockHttpClient(new MockResponse(json_encode([
                'choices' => [
                    ['message' => ['content' => '{"risk_score":25}']],
                ],
            ], JSON_THROW_ON_ERROR), ['response_headers' => ['x-request-id: req-test']])),
            logger: new NullLogger(),
            apiKey: 'test-key',
            model: 'test-model',
        );

        self::assertSame(['risk_score' => 25], $client->generateStructured('prompt'));
    }

    public function testMapsProviderErrorToExternalServiceException(): void
    {
        $client = new OpenAiLlmClient(
            client: new MockHttpClient(new MockResponse('{"error":{"type":"rate_limit"}}', ['http_code' => 429])),
            logger: new NullLogger(),
            apiKey: 'test-key',
            model: 'test-model',
        );

        $this->expectException(ExternalServiceException::class);

        $client->generateStructured('prompt');
    }
}
