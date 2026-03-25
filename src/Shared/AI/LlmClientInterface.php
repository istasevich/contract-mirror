<?php

declare(strict_types=1);

namespace App\Shared\AI;

interface LlmClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function generateStructured(string $prompt): array;
}
