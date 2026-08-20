<?php

declare(strict_types=1);

namespace App\Feature\Payment\Service;

use App\Repository\ContractReportRepository;
use App\Repository\ReportPaymentRepository;
use App\Shared\Decimal\Decimal;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VerifyTronPaymentService
{
    public function __construct(
        protected ReportPaymentRepository $paymentRepository,
        protected ContractReportRepository $reportRepository,
        protected EntityManagerInterface $em,
        protected HttpClientInterface $httpClient,
        protected LoggerInterface $logger,
        protected string $walletAddress,
        protected string $usdtContractAddress,
        protected int $minConfirmations,
    ) {
        // Nothing
    }

    public function handle(string $paymentId): bool
    {
        $payment = $this->paymentRepository->findOneById($paymentId);

        if ($payment === null) {
            throw new AccessDeniedHttpException('Payment not found.');
        }

        if ($payment->getStatus() === 'confirmed') {
            return true;
        }

        $txHash = $payment->getTxHash();

        if ($txHash === null || trim($txHash) === '') {
            throw new AccessDeniedHttpException('Transaction hash is required.');
        }

        $existingPayment = $this->paymentRepository->findOneBy([
            'txHash' => $txHash,
        ]);

        if ($existingPayment !== null && $existingPayment->getId() !== $payment->getId()) {
            throw new AccessDeniedHttpException('Transaction hash already used.');
        }

        $url = 'https://apilist.tronscanapi.com/api/transaction-info?hash=' . urlencode($txHash);

        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 10,
            'max_duration' => 15,
        ]);
        $rawBody = $response->getContent(false);

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            throw new AccessDeniedHttpException('Invalid transaction payload.');
        }

        //проверка статуса транзакции
        if (($payload['contractRet'] ?? null) !== 'SUCCESS') {
            throw new AccessDeniedHttpException('Transaction failed.');
        }

        if (($payload['confirmed'] ?? true) !== true) {
            throw new AccessDeniedHttpException('Transaction is not confirmed.');
        }

        $confirmations = (int) ($payload['confirmations'] ?? $payload['block'] ?? 0);

        if ($this->minConfirmations > 0 && $confirmations > 0 && $confirmations < $this->minConfirmations) {
            throw new AccessDeniedHttpException('Transaction does not have enough confirmations.');
        }

        $txDate = $this->resolveTransactionTimestamp($payload);

        if ($txDate === null) {
            throw new AccessDeniedHttpException('Transaction timestamp is missing.');
        }

        $allowedFrom = $payment->getCreatedAt()->modify('-30 minutes');

        if ($txDate < $allowedFrom) {
            $this->logger->warning('Old transaction rejected.', [
                'txDate' => $txDate->format(DATE_ATOM),
                'paymentCreatedAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            ]);

            throw new AccessDeniedHttpException('Transaction is too old for this payment.');
        }

        $transfers = $payload['trc20TransferInfo'] ?? null;

        if (!is_array($transfers)) {
            throw new AccessDeniedHttpException('Invalid transaction payload.');
        }

        foreach ($transfers as $transfer) {
            if (!is_array($transfer)) {
                continue;
            }

            $toAddress = (string) ($transfer['to_address'] ?? '');
            $tokenContract = (string) ($transfer['contract_address'] ?? '');
            $rawAmount = (string) ($transfer['amount_str'] ?? '0');
            $decimals = 6;
            $fromAddress = (string) ($transfer['from_address'] ?? '');

            if (mb_strtolower($toAddress) !== mb_strtolower($this->walletAddress)) {
                continue;
            }

            if ($this->usdtContractAddress !== '' && mb_strtolower($tokenContract) !== mb_strtolower($this->usdtContractAddress)) {
                continue;
            }

            if (Decimal::compareIntegerStrings($rawAmount, Decimal::toMinorUnits($payment->getExpectedAmount(), $decimals)) < 0) {
                continue;
            }

            return $this->confirmPayment($paymentId, $txHash, $fromAddress, $payload);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function confirmPayment(string $paymentId, string $txHash, string $fromAddress, array $payload): bool
    {
        return (bool) $this->em->wrapInTransaction(function () use ($paymentId, $txHash, $fromAddress, $payload): bool {
            $payment = $this->paymentRepository->findOneById($paymentId);

            if ($payment === null) {
                throw new AccessDeniedHttpException('Payment not found.');
            }

            if ($payment->getStatus() === 'confirmed') {
                return true;
            }

            $existingPayment = $this->paymentRepository->findOneBy([
                'txHash' => $txHash,
            ]);

            if ($existingPayment !== null && $existingPayment->getId() !== $payment->getId()) {
                throw new AccessDeniedHttpException('Transaction hash already used.');
            }

            $payment->setTxHash($txHash);
            $payment->markConfirmed(
                rawVerificationPayload: $this->buildSafeVerificationPayload($payload),
                payerAddress: $fromAddress !== '' ? $fromAddress : null,
            );

            $report = $payment->getReport();
            $report->unlock();

            $this->paymentRepository->save($payment);
            $this->reportRepository->save($report);
            $this->em->flush();

            $this->logger->info('TRON payment confirmed.', [
                'payment_id' => $paymentId,
                'report_id' => $report->getId(),
                'tx_hash' => hash('sha256', $txHash),
            ]);

            return true;
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildSafeVerificationPayload(array $payload): array
    {
        return [
            'contractRet' => $payload['contractRet'] ?? null,
            'confirmed' => $payload['confirmed'] ?? null,
            'confirmations' => $payload['confirmations'] ?? null,
            'blockTimeStamp' => $payload['blockTimeStamp'] ?? $payload['timestamp'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function resolveTransactionTimestamp(array $payload): ?\DateTimeImmutable
    {
        $timestampMs = $payload['blockTimeStamp']
            ?? $payload['timestamp']
            ?? (isset($payload['cost']['date_created']) ? ((int) $payload['cost']['date_created'] * 1000) : null);

        if ($timestampMs === null) {
            return null;
        }

        $timestampMs = (int) $timestampMs;

        if ($timestampMs <= 0) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp((int) floor($timestampMs / 1000));
    }
}
