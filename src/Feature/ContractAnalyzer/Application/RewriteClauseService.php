<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Application;

use App\Feature\ContractAnalyzer\Prompt\RewriteClausePromptFactory;
use App\Shared\AI\LlmClientInterface;

final class RewriteClauseService
{
    public function __construct(
        protected LlmClientInterface $llmClient,
        protected RewriteClausePromptFactory $promptFactory,
    ) {
        // Nothing
    }

    /**
     * @return array{rewrite: string, reasoning: string}
     */
    public function handle(string $clause, string $context, string $language): array
    {
        $prompt = $this->promptFactory->build(
            clause: $clause,
            context: $context,
            language: $language,
        );

        $response = $this->llmClient->generateStructured($prompt);

        return [
            'rewrite' => (string) ($response['rewrite'] ?? ''),
            'reasoning' => (string) ($response['reasoning'] ?? ''),
        ];
    }
}
