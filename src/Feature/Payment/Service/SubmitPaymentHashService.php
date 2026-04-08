<?php

declare(strict_types=1);

namespace App\Feature\Payment\Service;

use App\Entity\ReportPayment;
use App\Repository\ReportPaymentRepository;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
            throw new AccessDeniedHttpException('Payment not found.');
        }

        $txHash = trim($txHash);

        if ($txHash === '') {
            throw new AccessDeniedHttpException('Transaction hash is required.');
        }

        if (!$payment->isPending()) {
            throw new AccessDeniedHttpException('Payment is already processed.');
        }

        $existingPayment = $this->paymentRepository->findOneBy([
            'txHash' => $txHash,
        ]);

        if ($existingPayment !== null && $existingPayment->getId() !== $payment->getId()) {
            throw new AccessDeniedHttpException('This transaction hash has already been used.');
        }

        $payment->setTxHash($txHash);
        $this->paymentRepository->save($payment, true);

        $isConfirmed = $this->verifyTronPaymentService->handle($paymentId);

        if ($isConfirmed === false) {
            throw new AccessDeniedHttpException('Payment was not confirmed.');
        }

        $verifiedPayment = $this->paymentRepository->findOneById($paymentId);

        if ($verifiedPayment === null) {
            throw new AccessDeniedHttpException('Payment not found after verification.');
        }

        return $verifiedPayment;
    }
}
