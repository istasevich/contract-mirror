<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Mapper;

use App\Feature\ContractAnalyzer\DTO\ContractAnalysisReportViewDto;
use App\Feature\ContractAnalyzer\DTO\ContractIssueViewDto;
use App\Feature\ContractAnalyzer\DTO\MissingProtectionViewDto;

final class ContractAnalysisViewMapper
{
    /**
     * @param array<string, mixed> $payload
     */
    public function map(
        array $payload,
        string $documentName,
        string $documentType,
        string $language,
    ): ContractAnalysisReportViewDto {
        $issues = array_map(
            fn (array $issue): ContractIssueViewDto => new ContractIssueViewDto(
                title: (string) ($issue['title'] ?? 'Untitled issue'),
                severity: strtoupper((string) ($issue['severity'] ?? 'MEDIUM')),
                category: (string) ($issue['category'] ?? 'General'),
                originalClause: (string) ($issue['original_clause'] ?? ''),
                plainExplanation: (string) ($issue['plain_explanation'] ?? ''),
                whyItMatters: (string) ($issue['why_it_matters'] ?? ''),
                impact: (string) ($issue['impact'] ?? ''),
                riskExplanation: (string) ($issue['risk_level_explanation'] ?? ''),
                suggestedRewrite: (string) ($issue['suggested_rewrite'] ?? ''),
            ),
            array_values($payload['issues'] ?? [])
        );

        $missingProtections = array_map(
            fn (array $item): MissingProtectionViewDto => new MissingProtectionViewDto(
                title: (string) ($item['title'] ?? 'Missing protection'),
                category: (string) ($item['category'] ?? 'General'),
                whyItMatters: (string) ($item['why_it_matters'] ?? ''),
                suggestedClause: (string) ($item['suggested_clause'] ?? ''),
            ),
            array_values($payload['missing_protections'] ?? [])
        );

        return new ContractAnalysisReportViewDto(
            documentName: $documentName,
            documentType: $documentType,
            language: $language,
            riskScore: (int) ($payload['risk_score'] ?? 0),
            overallRisk: strtoupper((string) ($payload['overall_risk'] ?? 'MEDIUM')),
            riskSummary: (string) ($payload['risk_summary'] ?? ''),
            executiveSummary: (string) ($payload['executive_summary'] ?? ''),
            finalRecommendation: (string) ($payload['final_recommendation'] ?? ''),
            signingRecommendation: (string) ($payload['signing_recommendation'] ?? ''),
            issues: $issues,
            missingProtections: $missingProtections,
        );
    }
}
