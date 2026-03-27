<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\DTO;

final readonly class ProcessContractReportResultDto
{
    public function __construct(
        public string $publicId,
        public string $html,
        public string $reportTitle,
        public string $reportSubtitle,
        public bool $isLocked,
        public string $paymentId,
        public string $paymentAddress,
        public string $paymentAmount,
        public string $paymentCurrency,
    ) {
        // Nothing
    }
}
