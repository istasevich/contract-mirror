<?php

namespace App\Shared\Exception;

final class ReportLockedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Report is locked. Please unlock full report to download.');
    }
}
