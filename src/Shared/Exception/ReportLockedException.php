<?php

namespace App\Shared\Exception;

final class ReportLockedException extends \RuntimeException
{
    public function __construct($message = 'Report is locked. Please unlock full report to download.', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
