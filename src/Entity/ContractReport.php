<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContractReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ContractReportRepository::class)]
#[ORM\Table(name: 'contract_reports')]
class ContractReport
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $id;

    #[ORM\Column(length: 32, unique: true)]
    protected string $publicId;

    #[ORM\Column(length: 255)]
    protected string $fileName;

    #[ORM\Column(length: 64)]
    protected string $fileHash;

    #[ORM\Column(length: 32)]
    protected string $documentType;

    #[ORM\Column(length: 32)]
    protected string $language;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $riskScore = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $overallRisk = null;

    #[ORM\Column(length: 16)]
    protected string $status = 'pending';

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $reportPayload = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $reportHtml = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $isLocked = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $updatedAt;

    public function __construct(
        string $publicId,
        string $fileName,
        string $fileHash,
        string $documentType,
        string $language,
    ) {
        $this->id = Uuid::v7();
        $this->publicId = $publicId;
        $this->fileName = $fileName;
        $this->fileHash = $fileHash;
        $this->documentType = $documentType;
        $this->language = $language;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPublicId(): string
    {
        return $this->publicId;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getRiskScore(): ?int
    {
        return $this->riskScore;
    }

    public function getOverallRisk(): ?string
    {
        return $this->overallRisk;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getReportPayload(): ?array
    {
        return $this->reportPayload;
    }

    public function getReportHtml(): ?string
    {
        return $this->reportHtml;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param array<string, mixed> $reportPayload
     */
    public function markReady(
        int $riskScore,
        string $overallRisk,
        array $reportPayload,
        string $reportHtml,
    ): void {
        $this->riskScore = $riskScore;
        $this->overallRisk = $overallRisk;
        $this->reportPayload = $reportPayload;
        $this->reportHtml = $reportHtml;
        $this->status = 'ready';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->status = 'failed';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function unlock(): void
    {
        $this->isLocked = false;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
