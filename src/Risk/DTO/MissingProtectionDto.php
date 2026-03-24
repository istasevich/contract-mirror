<?php declare(strict_types=1);

namespace App\Risk\DTO;

final readonly class MissingProtectionDto
{
    public function __construct(
        public string $title,
        public string $explanation,
        public string $suggestedClause,
    ) {
        // Nothing
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            title: (string) ($payload['title'] ?? ''),
            explanation: (string) ($payload['explanation'] ?? ''),
            suggestedClause: (string) ($payload['suggested_clause'] ?? ''),
        );
    }
}
