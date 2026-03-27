<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Repository\ContractReportRepository;
use App\Repository\ReportPaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ViewContractReportController extends AbstractController
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
        protected ReportPaymentRepository $paymentRepository,
    ) {
        // Nothing
    }

    #[Route('/r/{publicId}', name: 'app_contract_report_view', methods: ['GET'])]
    public function __invoke(string $publicId): Response
    {
        $report = $this->reportRepository->findOneByPublicId($publicId);

        if ($report === null) {
            throw $this->createNotFoundException('Report not found.');
        }

        $isLocked = $report->isLocked();

        $paymentEntity = $isLocked
            ? $this->paymentRepository->findOneBy([
                'report' => $report,
                'status' => 'pending',
            ])
            : null;

        $payment = $paymentEntity === null ? null : [
            'paymentId' => $paymentEntity->getId(),
            'address' => $paymentEntity->getWalletAddress(),
            'amount' => $paymentEntity->getExpectedAmount(),
            'currency' => $paymentEntity->getCurrency(),
        ];

        $initialReportHtml = $this->renderView('contract/_report.html.twig', [
            'report' => $report->getReportPayload(),
            'payment' => $payment,
            'isLocked' => $isLocked,
        ]);

        return $this->render('landing/index.html.twig', [
            'initialReportHtml' => $initialReportHtml,
            'initialReportTitle' => $report->getFileName(),
            'initialReportSubtitle' => 'Review the verdict, risks, and suggested fixes.',
            'initialIsLocked' => $isLocked,
            'isLocked' => $isLocked,
            'initialPublicId' => $report->getPublicId(),
            'initialPayment' => $payment,
        ]);
    }
}
