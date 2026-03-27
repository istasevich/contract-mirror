<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReportPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReportPaymentRepository::class)]
#[ORM\Table(name: 'report_payments')]
class ReportPayment
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    protected string $id;

    #[ORM\ManyToOne(targetEntity: ContractReport::class)]
    #[ORM\JoinColumn(name: 'report_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ContractReport $report;

    #[ORM\Column(length: 32)]
    protected string $paymentMethod = 'usdt_trc20';

    #[ORM\Column(length: 16)]
    protected string $walletNetwork = 'TRC20';

    #[ORM\Column(length: 255)]
    protected string $walletAddress;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    protected string $expectedAmount;

    #[ORM\Column(length: 10)]
    protected string $currency = 'USDT';

    #[ORM\Column(length: 128, nullable: true)]
    protected ?string $txHash = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $payerAddress = null;

    #[ORM\Column(length: 20)]
    protected string $status = 'pending';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $rawVerificationPayload = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $updatedAt;

    public function __construct(
        ContractReport $report,
        string $walletAddress,
        string $expectedAmount,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->report = $report;
        $this->walletAddress = $walletAddress;
        $this->expectedAmount = $expectedAmount;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getReport(): ContractReport
    {
        return $this->report;
    }

    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }

    public function getExpectedAmount(): string
    {
        return $this->expectedAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTxHash(): ?string
    {
        return $this->txHash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setTxHash(string $txHash): void
    {
        $this->txHash = trim($txHash);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markConfirmed(array $rawVerificationPayload, ?string $payerAddress = null): void
    {
        $this->status = 'confirmed';
        $this->rawVerificationPayload = $rawVerificationPayload;
        $this->payerAddress = $payerAddress;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markRejected(array $rawVerificationPayload = []): void
    {
        $this->status = 'rejected';
        $this->rawVerificationPayload = $rawVerificationPayload;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
