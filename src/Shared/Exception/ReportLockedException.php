<?php

declare(strict_types=1);

namespace App\Shared\Exception;

final class ReportLockedException extends \RuntimeException
{
    public function __construct(string $message = 'Report is locked. Please unlock full report to download.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
