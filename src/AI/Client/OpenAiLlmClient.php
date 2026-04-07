<?php

declare(strict_types=1);

namespace App\AI\Client;

use App\Shared\AI\LlmClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
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
        ]);

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getContent(false);

        $this->logger->info('OpenAI raw response received.', [
            'status_code' => $statusCode,
            'raw_body' => $rawBody,
        ]);

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Failed to decode raw HTTP response.', [
                'raw_body' => $rawBody,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Failed to decode HTTP response.', 0, $exception);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            $this->logger->error('Empty or invalid OpenAI response payload.', [
                'payload' => $payload,
            ]);

            throw new RuntimeException('Empty or invalid response payload.');
        }

        $this->logger->info('OpenAI content extracted.', [
            'content' => $content,
        ]);

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Failed to decode model JSON response.', [
                'content' => $content,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Failed to decode model JSON response.', 0, $exception);
        }

        if (!is_array($decoded)) {
            $this->logger->error('Model returned non-object JSON.', [
                'decoded' => $decoded,
            ]);

            throw new RuntimeException('Model returned non-object JSON.');
        }

        $this->logger->info('OpenAI structured response decoded successfully.', [
            'decoded' => $decoded,
        ]);

        return $decoded;
    }
}
