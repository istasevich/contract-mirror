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

        if ($payment->getTxHash() === null || trim($payment->getTxHash()) === '') {
            return false;
        }

        $url = 'https://apilist.tronscanapi.com/api/transaction-info?hash=' . urlencode($payment->getTxHash());

        $response = $this->httpClient->request('GET', $url);
        $rawBody = $response->getContent(false);

        $this->logger->info('TRON verify response received.', [
            'paymentId' => $paymentId,
            'txHash' => $payment->getTxHash(),
            'rawBody' => $rawBody,
        ]);

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            return false;
        }

        $transfers = $payload['trc20TransferInfo'] ?? null;

        if (!is_array($transfers)) {
            return false;
        }

        foreach ($transfers as $transfer) {
            if (!is_array($transfer)) {
                continue;
            }

            $toAddress = (string) ($transfer['to_address'] ?? '');
            $tokenContract = (string) ($transfer['contract_address'] ?? '');
            $amountString = (string) ($transfer['amount_str'] ?? '0');
            $fromAddress = (string) ($transfer['from_address'] ?? '');

            if ($toAddress === '') {
                continue;
            }

            if (mb_strtolower($toAddress) !== mb_strtolower($this->walletAddress)) {
                continue;
            }

            if ($this->usdtContractAddress !== '' && mb_strtolower($tokenContract) !== mb_strtolower($this->usdtContractAddress)) {
                continue;
            }

            if ((float) $amountString < (float) $payment->getExpectedAmount()) {
                continue;
            }

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
}
