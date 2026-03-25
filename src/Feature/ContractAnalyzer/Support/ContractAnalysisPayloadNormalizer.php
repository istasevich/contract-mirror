<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Support;

final class ContractAnalysisPayloadNormalizer
{
    public function normalize(array $payload): array
    {
        $riskScore = (int) ($payload['risk_score'] ?? 0);
        $riskScore = max(0, min(100, $riskScore));

        $overallRisk = strtoupper((string) ($payload['overall_risk'] ?? 'MEDIUM'));
        if (!in_array($overallRisk, ['LOW', 'MEDIUM', 'HIGH'], true)) {
            $overallRisk = 'MEDIUM';
        }

        $issues = [];
        foreach ((array) ($payload['issues'] ?? []) as $issue) {
            if (!is_array($issue)) {
                continue;
            }

            $severity = strtoupper((string) ($issue['severity'] ?? 'MEDIUM'));
            if (!in_array($severity, ['LOW', 'MEDIUM', 'HIGH'], true)) {
                $severity = 'MEDIUM';
            }

            $issues[] = [
                'title' => (string) ($issue['title'] ?? 'Untitled issue'),
                'severity' => $severity,
                'category' => (string) ($issue['category'] ?? 'General'),
                'original_clause' => (string) ($issue['original_clause'] ?? ''),
                'plain_explanation' => (string) ($issue['plain_explanation'] ?? ''),
                'why_it_matters' => (string) ($issue['why_it_matters'] ?? ''),
                'suggested_rewrite' => (string) ($issue['suggested_rewrite'] ?? ''),
            ];
        }

        $missingProtections = [];
        foreach ((array) ($payload['missing_protections'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $missingProtections[] = [
                'title' => (string) ($item['title'] ?? 'Missing protection'),
                'category' => (string) ($item['category'] ?? 'General'),
                'why_it_matters' => (string) ($item['why_it_matters'] ?? ''),
                'suggested_clause' => (string) ($item['suggested_clause'] ?? ''),
            ];
        }

        return [
            'risk_score' => $riskScore,
            'overall_risk' => $overallRisk,
            'executive_summary' => (string) ($payload['executive_summary'] ?? ''),
            'final_recommendation' => (string) ($payload['final_recommendation'] ?? ''),
            'signing_recommendation' => (string) ($payload['signing_recommendation'] ?? ''),
            'issues' => $issues,
            'missing_protections' => $missingProtections,
        ];
    }
}
