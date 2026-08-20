<?php

declare(strict_types=1);

namespace App\Shared\Service;

final class Json
{
    /**
     * @return array<string, mixed>
     */
    public static function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
