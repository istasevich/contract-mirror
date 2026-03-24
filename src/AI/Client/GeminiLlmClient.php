<?php declare(strict_types=1);

namespace App\AI\Client;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client for Google Gemini API (2026 Edition)
 */
final readonly class GeminiLlmClient implements LlmClientInterface
{
    private const string API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
        private string $model = 'gemini-1.5-pro', // Или gemini-2.0-flash для скорости
    ) {}

    public function generateJson(string $prompt): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $url = sprintf(self::API_URL, $this->model, $this->apiKey);

        $response = $this->client->request('POST', $url, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.1,
                ],
            ],
        ]);

        $payload = $response->toArray();

        // Извлекаем текст из структуры ответа Gemini
        $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text) || $text === '') {
            throw new RuntimeException('Empty or invalid Gemini response payload.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}