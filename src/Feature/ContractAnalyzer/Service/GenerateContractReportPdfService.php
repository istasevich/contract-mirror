<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use App\Entity\ContractReport;
use App\Shared\Exception\ReportLockedException;
use Spatie\Browsershot\Browsershot;
use Twig\Environment;

final class GenerateContractReportPdfService
{
    public function __construct(
        protected Environment $twig,
    ) {
        // Nothing
    }

    public function handle(ContractReport $report): string
    {
        $payload = $report->getReportPayload();

        if (!is_array($payload)) {
            throw new ReportLockedException('Invalid report payload.');
        }

        $html = $this->twig->render('contract/pdf.html.twig', [
            'report' => [
                'publicId' => $report->getPublicId(),

                'documentName' => $payload['documentName'] ?? 'Document',
                'documentType' => $payload['documentType'] ?? 'Contract',
                'language' => $payload['language'] ?? 'Unknown',
                'signingRecommendation' => $payload['signingRecommendation'] ?? null,
                'riskScore' => $payload['riskScore'] ?? $report->getRiskScore(),
                'overallRisk' => strtoupper($payload['overallRisk'] ?? 'MEDIUM'),
                'summary' => $payload['executiveSummary'] ?? '',
                'issues' => array_map(fn ($issue) => [
                    'title' => $issue['title'] ?? '',
                    'severity' => strtoupper($issue['severity'] ?? 'MEDIUM'),
                    'category' => $issue['category'] ?? null,
                    'originalClause' => $issue['originalClause'] ?? null,
                    'explanation' => $issue['plainExplanation'] ?? null,
                    'risk' => $issue['whyItMatters'] ?? null,
                    'suggestion' => $issue['suggestedRewrite'] ?? null,
                ], $payload['issues'] ?? []),

                'missingProtections' => array_map(fn ($item) => [
                    'title' => $item['title'] ?? '',
                    'category' => $item['category'] ?? null,
                    'explanation' => $item['whyItMatters'] ?? null,
                    'suggestion' => $item['suggestedClause'] ?? null,
                ], $payload['missingProtections'] ?? []),
            ],
        ]);

        return Browsershot::html($html)
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setChromePath('/usr/bin/chromium-browser')
            ->noSandbox()
            ->showBackground()
            ->format('A4')
            ->margins(15, 12, 15, 12)
            ->timeout(120)
            ->pdf();
    }
}
