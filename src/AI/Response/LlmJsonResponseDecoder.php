<?php

declare(strict_types=1);

namespace App\AI\Response;

use JsonException;
use RuntimeException;

final class LlmJsonResponseDecoder
{
    /**
     * @return array<string, mixed>
     */
    public function decode(string $content): array
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to decode LLM JSON response.', previous: $exception);
        }

        return $data;
    }
}
