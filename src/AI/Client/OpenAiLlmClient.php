<?php declare(strict_types=1);

namespace App\AI\Client;

use JsonException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenAiLlmClient implements LlmClientInterface
{
    public function __construct(
        protected HttpClientInterface $client,
        protected string $apiKey,
        protected string $model,
    ) {
        // Nothing
    }

    public function generateJson(string $prompt): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 90,
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return valid JSON only. Do not wrap JSON in markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                    'temperature' => 0.2,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            throw new RuntimeException('OpenAI request failed: '.$exception->getMessage(), previous: $exception);
        }

        if ($statusCode >= 400) {
            $message = is_array($payload['error'] ?? null)
                ? (string) ($payload['error']['message'] ?? 'Unknown OpenAI error.')
                : 'Unknown OpenAI error.';

            throw new RuntimeException(sprintf('OpenAI API error (%d): %s', $statusCode, $message));
        }

        $text = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenAI returned an empty response payload.');
        }

        $text = $this->normalizeJsonText($text);

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to decode JSON returned by OpenAI.', previous: $exception);
        }

        return $decoded;
    }

    private function normalizeJsonText(string $text): string
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
        }

        return trim($text);
    }
}
