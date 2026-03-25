<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Prompt;

final class RewriteClausePromptFactory
{
    public function build(string $clause, string $context, string $language): string
    {
        return <<<PROMPT
You are a contract risk assistant for freelancers and independent contractors.

Your task:
Rewrite the contract clause so that it is safer, more balanced, and more reasonable for the contractor/freelancer, while keeping it commercially realistic.

Rules:
- Keep the rewrite concise and contract-style.
- Do not make it overly aggressive or unrealistic.
- Preserve the general business intent.
- Improve fairness, clarity, and risk allocation.
- Write the output in {$language}.
- Return valid JSON only.

Required JSON structure:
{
  "rewrite": "string",
  "reasoning": "string"
}

Original clause:
{$clause}

Context:
{$context}
PROMPT;
    }
}
