<?php declare(strict_types=1);

namespace App\AI\Service;

use App\AI\Client\LlmClientInterface;
use App\AI\Prompt\ClauseRewritePromptFactory;

final readonly class ClauseRewriteAiService
{
    public function __construct(
        protected LlmClientInterface $llmClient,
        protected ClauseRewritePromptFactory $promptFactory,
    ) {
        // Nothing
    }

    /**
     * @return array<string, mixed>
     */
    public function rewrite(string $clauseText, string $issueTitle): array
    {
        $prompt = $this->promptFactory->build(
            clauseText: $clauseText,
            issueTitle: $issueTitle,
        );

        return $this->llmClient->generateJson($prompt);
    }
}
