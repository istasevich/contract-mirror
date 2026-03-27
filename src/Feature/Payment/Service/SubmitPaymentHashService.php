<?php

declare(strict_types=1);

namespace App\Feature\Payment\Service;

use App\Entity\ReportPayment;
use App\Repository\ReportPaymentRepository;
use RuntimeException;

final class SubmitPaymentHashService
{
    public function __construct(
        protected ReportPaymentRepository $paymentRepository,
    ) {
        // Nothing
    }

    public function handle(string $paymentId, string $txHash): ReportPayment
    {
        $payment = $this->paymentRepository->findOneById($paymentId);

        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        if (trim($txHash) === '') {
            throw new RuntimeException('Transaction hash is required.');
        }

        $payment->setTxHash($txHash);

        $this->paymentRepository->save($payment, true);

        return $payment;
    }
}
