<?php declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use App\AI\Service\ContractAnalysisAiService;
use App\Document\Service\DocumentTextExtractor;
use App\Feature\ContractAnalyzer\DTO\AnalyzeContractCommand;
use App\Risk\DTO\MissingProtectionDto;
use App\Risk\DTO\RiskIssueDto;
use App\Risk\DTO\RiskReportDto;
use App\Risk\Service\RiskScoreCalculator;

final readonly class AnalyzeContractService
{
    public function __construct(
        protected DocumentTextExtractor $documentTextExtractor,
        protected ContractAnalysisAiService $contractAnalysisAiService,
        protected RiskScoreCalculator $riskScoreCalculator,
    ) {
        // Nothing
    }

    public function run(AnalyzeContractCommand $command): RiskReportDto
    {
        $document = $this->documentTextExtractor->extract($command->file);

        $analysis = $this->contractAnalysisAiService->analyze(
            contractText: $document->content,
            preferredLanguage: $command->preferredLanguage,
        );

        /** @var array<int, array<string, mixed>> $issuesPayload */
        $issuesPayload = is_array($analysis['issues'] ?? null) ? $analysis['issues'] : [];
        /** @var array<int, array<string, mixed>> $missingProtectionsPayload */
        $missingProtectionsPayload = is_array($analysis['missing_protections'] ?? null) ? $analysis['missing_protections'] : [];

        $score = $this->riskScoreCalculator->calculate($issuesPayload);

        return new RiskReportDto(
            documentType: (string) ($analysis['document_type'] ?? $document->documentType->value),
            language: (string) ($analysis['language'] ?? $command->preferredLanguage),
            summary: (string) ($analysis['summary'] ?? ''),
            riskScore: $score['riskScore'],
            overallRiskLevel: $score['overallRiskLevel'],
            issues: array_map(static fn (array $issue): RiskIssueDto => RiskIssueDto::fromArray($issue), $issuesPayload),
            missingProtections: array_map(static fn (array $item): MissingProtectionDto => MissingProtectionDto::fromArray($item), $missingProtectionsPayload),
            finalRecommendation: (string) ($analysis['final_recommendation'] ?? ''),
        );
    }
}
