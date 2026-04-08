<?php

declare(strict_types=1);

namespace App\Feature\Payment\Service;

use App\Repository\ContractReportRepository;
use App\Repository\ReportPaymentRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VerifyTronPaymentService
{
    public function __construct(
        protected ReportPaymentRepository $paymentRepository,
        protected ContractReportRepository $reportRepository,
        protected HttpClientInterface $httpClient,
        protected LoggerInterface $logger,
        protected string $walletAddress,
        protected string $usdtContractAddress,
    ) {
        // Nothing
    }

    public function handle(string $paymentId): bool
    {
        $payment = $this->paymentRepository->findOneById($paymentId);

        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        if ($payment->getStatus() === 'confirmed') {
            return true;
        }

        $txHash = $payment->getTxHash();

        if ($txHash === null || trim($txHash) === '') {
            throw new RuntimeException('Transaction hash is required.');
        }

        $existingPayment = $this->paymentRepository->findOneBy([
            'txHash' => $txHash,
        ]);

        if ($existingPayment !== null && $existingPayment->getId() !== $payment->getId()) {
            throw new RuntimeException('Transaction hash already used.');
        }

        $url = 'https://apilist.tronscanapi.com/api/transaction-info?hash=' . urlencode($txHash);

        $response = $this->httpClient->request('GET', $url);
        $rawBody = $response->getContent(false);

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid transaction payload.');
        }

        //проверка статуса транзакции
        if (($payload['contractRet'] ?? null) !== 'SUCCESS') {
            throw new RuntimeException('Transaction failed.');
        }

        // защита от старых транзакций
        $txDate = $this->resolveTransactionTimestamp($payload);

        if ($txDate === null) {
            throw new RuntimeException('Transaction timestamp is missing.');
        }

        $allowedFrom = $payment->getCreatedAt()->modify('-30 minutes');

        if ($txDate < $allowedFrom) {
            $this->logger->warning('Old transaction rejected.', [
                'txDate' => $txDate->format(DATE_ATOM),
                'paymentCreatedAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            ]);

           // throw new RuntimeException('Transaction is too old for this
            // payment.');
        }

        $transfers = $payload['trc20TransferInfo'] ?? null;

        if (!is_array($transfers)) {
            throw new RuntimeException('Invalid transaction payload.');
        }

        foreach ($transfers as $transfer) {
            if (!is_array($transfer)) {
                continue;
            }

            $toAddress = (string) ($transfer['to_address'] ?? '');
            $tokenContract = (string) ($transfer['contract_address'] ?? '');
            $rawAmount = (float) ($transfer['amount_str'] ?? '0');
            $decimals = 6;

            $amount = $rawAmount / (10 ** $decimals);

            if ($amount < (float) $payment->getExpectedAmount()) {
                continue;
            }
            $fromAddress = (string) ($transfer['from_address'] ?? '');

            // проверка адреса
            if (mb_strtolower($toAddress) !== mb_strtolower($this->walletAddress)) {
                continue;
            }

            // проверка контракта (USDT)
            if ($this->usdtContractAddress !== '' && mb_strtolower($tokenContract) !== mb_strtolower($this->usdtContractAddress)) {
                continue;
            }

            if ($amount < $payment->getExpectedAmount()) {
                continue;
            }


            $payment->setTxHash($txHash);

            $payment->markConfirmed(
                rawVerificationPayload: $payload,
                payerAddress: $fromAddress !== '' ? $fromAddress : null,
            );

            $report = $payment->getReport();
            $report->unlock();

            $this->paymentRepository->save($payment, true);
            $this->reportRepository->save($report, true);

            $this->logger->info('TRON payment confirmed.', [
                'paymentId' => $paymentId,
                'reportId' => $report->getId(),
            ]);

            return true;
        }

        return false;
    }

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
