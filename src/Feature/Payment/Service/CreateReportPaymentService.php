<?php

declare(strict_types=1);

namespace App\Feature\Payment\Service;

use App\Entity\ContractReport;
use App\Entity\ReportPayment;
use App\Repository\ReportPaymentRepository;

final class CreateReportPaymentService
{
    public function __construct(
        protected ReportPaymentRepository $paymentRepository,
        protected string $walletAddress,
    ) {
        // Nothing
    }

    public function handle(ContractReport $report): ReportPayment
    {
        $payment = new ReportPayment(
            report: $report,
            walletAddress: $this->walletAddress,
            expectedAmount: '9.00',
        );

        $this->paymentRepository->save($payment, true);

        return $payment;
    }
}
