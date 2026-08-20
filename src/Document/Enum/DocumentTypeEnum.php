<?php

declare(strict_types=1);

namespace App\Document\Enum;

enum DocumentTypeEnum: string
{
    case PDF = 'pdf';
    case DOCX = 'docx';
    case TXT = 'txt';
    case UNKNOWN = 'unknown';
}
