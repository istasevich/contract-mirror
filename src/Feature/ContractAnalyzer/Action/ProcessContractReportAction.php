<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Action;

use App\Feature\ContractAnalyzer\DTO\ProcessContractReportResultDto;
use App\Feature\ContractAnalyzer\Request\AnalyzeContractRequest;
use App\Feature\ContractAnalyzer\Service\AnalyzeContractService;
use App\Feature\ContractAnalyzer\Service\CreateContractReportService;
use App\Feature\ContractAnalyzer\Service\FinalizeContractReportService;
use App\Feature\Payment\Service\CreateReportPaymentService;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class ProcessContractReportAction
{
    public function __construct(
        protected CreateContractReportService $createContractReportService,
        protected AnalyzeContractService $analyzeContractService,
        protected FinalizeContractReportService $finalizeContractReportService,
        protected CreateReportPaymentService $createReportPaymentService,
        protected Environment $twig,
    ) {
        // Nothing
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function execute(AnalyzeContractRequest $request, bool $isAllowed = false): ProcessContractReportResultDto
    {
        $reportView = $this->analyzeContractService->handle($request);

        $storedReport = $this->createContractReportService->handle(
            file: $request->file,
            documentType: $reportView->documentType,
            language: $request->preferredLanguage,
        );

        if ($isAllowed) {
            $storedReport->unlock();
        }

        $payment = $this->createReportPaymentService->handle($storedReport);

        $html = $this->twig->render('contract/_report.html.twig', [
            'report' => $reportView,
            'publicId' => $storedReport->getPublicId(),
            'isLocked' => $storedReport->isLocked(),
            'payment' => [
                'paymentId' => $payment->getId(),
                'address' => $payment->getWalletAddress(),
                'amount' => $payment->getExpectedAmount(),
                'currency' => $payment->getCurrency(),
            ],
        ]);

        $this->finalizeContractReportService->handle(
            report: $storedReport,
            riskScore: $reportView->riskScore,
            overallRisk: $reportView->overallRisk,
            reportPayload: $this->buildReportPayload($reportView),
            reportHtml: $html,
        );

        return new ProcessContractReportResultDto(
            publicId: $storedReport->getPublicId(),
            html: $html,
            reportTitle: $storedReport->getFileName(),
            reportSubtitle: 'Review the verdict, risks, and suggested fixes.',
            isLocked: $storedReport->isLocked(),
            paymentId: (string)$payment->getId(),
            paymentAddress: $payment->getWalletAddress(),
            paymentAmount: $payment->getExpectedAmount(),
            paymentCurrency: $payment->getCurrency(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildReportPayload(object $reportView): array
    {
        return [
            'documentName' => $reportView->documentName,
            'documentType' => $reportView->documentType,
            'language' => $reportView->language,
            'riskScore' => $reportView->riskScore,
            'overallRisk' => $reportView->overallRisk,
            'executiveSummary' => $reportView->executiveSummary,
            'finalRecommendation' => $reportView->finalRecommendation,
            'signingRecommendation' => $reportView->signingRecommendation,
            'issues' => array_map(
                static fn (object $issue): array => [
                    'title' => $issue->title,
                    'severity' => $issue->severity,
                    'category' => $issue->category,
                    'originalClause' => $issue->originalClause,
                    'plainExplanation' => $issue->plainExplanation,
                    'whyItMatters' => $issue->whyItMatters,
                    'suggestedRewrite' => $issue->suggestedRewrite,
                ],
                $reportView->issues,
            ),
            'missingProtections' => array_map(
                static fn (object $item): array => [
                    'title' => $item->title,
                    'category' => $item->category,
                    'whyItMatters' => $item->whyItMatters,
                    'suggestedClause' => $item->suggestedClause,
                ],
                $reportView->missingProtections,
            ),
        ];
    }
}
