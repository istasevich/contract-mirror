<?php declare(strict_types=1);

namespace App\AI\Client;

interface LlmClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt): array;
}
