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
        protected VerifyTronPaymentService $verifyTronPaymentService,
    ) {
        // Nothing
    }

    public function handle(string $paymentId, string $txHash): ReportPayment
    {
        $payment = $this->paymentRepository->findOneById($paymentId);

        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        $txHash = trim($txHash);

        if ($txHash === '') {
            throw new RuntimeException('Transaction hash is required.');
        }

        if (!$payment->isPending()) {
            throw new RuntimeException('Payment is already processed.');
        }

        $existingPayment = $this->paymentRepository->findOneBy([
            'txHash' => $txHash,
        ]);

        if ($existingPayment !== null && $existingPayment->getId() !== $payment->getId()) {
            throw new RuntimeException('This transaction hash has already been used.');
        }

        $payment->setTxHash($txHash);
        $this->paymentRepository->save($payment, true);

        $isConfirmed = $this->verifyTronPaymentService->handle($paymentId);

        if ($isConfirmed === false) {
            throw new RuntimeException('Payment was not confirmed.');
        }

        $verifiedPayment = $this->paymentRepository->findOneById($paymentId);

        if ($verifiedPayment === null) {
            throw new RuntimeException('Payment not found after verification.');
        }

        return $verifiedPayment;
    }
}
