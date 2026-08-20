<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use App\Entity\ContractReport;
use App\Repository\ContractReportRepository;

final class FinalizeContractReportService
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
    ) {
        // Nothing
    }

    /**
     * @param array<string, mixed> $reportPayload
     */
    public function handle(
        ContractReport $report,
        int $riskScore,
        string $overallRisk,
        array $reportPayload,
        string $reportHtml,
    ): void {
        $report->markReady(
            riskScore: $riskScore,
            overallRisk: $overallRisk,
            reportPayload: $reportPayload,
            reportHtml: $reportHtml,
        );

        $this->reportRepository->save($report, true);
    }
}
