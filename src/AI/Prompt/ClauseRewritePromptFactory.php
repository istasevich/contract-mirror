<?php declare(strict_types=1);

namespace App\AI\Prompt;

final class ClauseRewritePromptFactory
{
    public function build(string $clauseText, string $issueTitle): string
    {
        return <<<PROMPT
You are rewriting a risky contract clause for a freelancer.

Goal:
- keep the business meaning
- reduce unfair risk
- make the clause clearer and more balanced
- keep it concise and professional

Return valid JSON only with this structure:
{
  "rewritten_clause": "string",
  "explanation": "string"
}

Issue detected: {$issueTitle}

Original clause:
---
{$clauseText}
---
PROMPT;
    }
}
