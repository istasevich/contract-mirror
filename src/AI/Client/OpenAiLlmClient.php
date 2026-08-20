<?php

declare(strict_types=1);

namespace App\AI\Client;

use App\Shared\AI\LlmClientInterface;
use App\Shared\Exception\ExternalServiceException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenAiLlmClient implements LlmClientInterface
{
    public function __construct(
        protected HttpClientInterface $client,
        protected LoggerInterface $logger,
        protected string $apiKey,
        protected string $model = 'gpt-4.1-mini',
    ) {
        // Nothing
    }

    public function generateStructured(string $prompt): array
    {
        $startedAt = microtime(true);

        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a precise contract analysis assistant. Return valid JSON only.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                ],
                'timeout' => 30,
                'max_duration' => 45,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('OpenAI request failed.', [
                'model' => $this->model,
                'latency_ms' => $this->latencyMs($startedAt),
                'error_type' => $exception::class,
            ]);

            throw new ExternalServiceException('The AI provider is temporarily unavailable.', 0, $exception);
        }

        $requestId = $response->getHeaders(false)['x-request-id'][0] ?? null;

        $this->logger->info('OpenAI response received.', [
            'status_code' => $statusCode,
            'model' => $this->model,
            'request_id' => $requestId,
            'latency_ms' => $this->latencyMs($startedAt),
        ]);

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('OpenAI returned an error status.', [
                'status_code' => $statusCode,
                'model' => $this->model,
                'request_id' => $requestId,
                'error_type' => $this->extractProviderErrorType($rawBody),
            ]);

            throw new ExternalServiceException('The AI provider could not process the request.');
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Failed to decode OpenAI HTTP response.', [
                'status_code' => $statusCode,
                'model' => $this->model,
                'request_id' => $requestId,
            ]);

            throw new ExternalServiceException('The AI provider returned an invalid response.', 0, $exception);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            $this->logger->error('Empty or invalid OpenAI response payload.', [
                'status_code' => $statusCode,
                'model' => $this->model,
                'request_id' => $requestId,
            ]);

            throw new ExternalServiceException('The AI provider returned an invalid response.');
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Failed to decode model JSON response.', [
                'status_code' => $statusCode,
                'model' => $this->model,
                'request_id' => $requestId,
            ]);

            throw new ExternalServiceException('The AI provider returned malformed JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            $this->logger->error('Model returned non-object JSON.', [
                'status_code' => $statusCode,
                'model' => $this->model,
                'request_id' => $requestId,
            ]);

            throw new ExternalServiceException('The AI provider returned an invalid response.');
        }

        $this->logger->info('OpenAI structured response decoded successfully.', [
            'status_code' => $statusCode,
            'model' => $this->model,
            'request_id' => $requestId,
            'latency_ms' => $this->latencyMs($startedAt),
        ]);

        return $decoded;
    }

    private function latencyMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function extractProviderErrorType(string $rawBody): ?string
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $type = $payload['error']['type'] ?? null;

        return is_string($type) ? $type : null;
    }
}
