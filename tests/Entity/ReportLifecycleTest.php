<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ContractReport;
use App\Entity\ReportPayment;
use PHPUnit\Framework\TestCase;

final class ReportLifecycleTest extends TestCase
{
    public function testReportCanBeFinalizedAndUnlocked(): void
    {
        $report = new ContractReport('CMR-12345678', 'contract.pdf', str_repeat('a', 64), 'PDF', 'en');

        $report->markReady(70, 'HIGH', ['risk_score' => 70], '<section>report</section>');
        $report->unlock();

        self::assertSame('ready', $report->getStatus());
        self::assertFalse($report->isLocked());
        self::assertSame(70, $report->getRiskScore());
        self::assertSame(['risk_score' => 70], $report->getReportPayload());
    }

    public function testPaymentConfirmationStoresSanitizedVerificationPayload(): void
    {
        $report = new ContractReport('CMR-12345678', 'contract.pdf', str_repeat('a', 64), 'PDF', 'en');
        $payment = new ReportPayment($report, 'TWallet', '9.00');

        $payment->setTxHash('tx-1');
        $payment->markConfirmed(['contractRet' => 'SUCCESS'], 'payer');

        self::assertSame('confirmed', $payment->getStatus());
        self::assertSame('tx-1', $payment->getTxHash());
    }
}
