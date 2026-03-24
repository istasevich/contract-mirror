<?php declare(strict_types=1);

namespace App\AI\Service;

use App\AI\Client\LlmClientInterface;
use App\AI\Prompt\ContractAnalysisPromptFactory;

final readonly class ContractAnalysisAiService
{
    public function __construct(
        protected LlmClientInterface $llmClient,
        protected ContractAnalysisPromptFactory $promptFactory,
    ) {
        // Nothing
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $contractText, string $preferredLanguage): array
    {
        $prompt = $this->promptFactory->build(
            contractText: $contractText,
            preferredLanguage: $preferredLanguage,
        );

        return $this->llmClient->generateJson($prompt);
    }
}
