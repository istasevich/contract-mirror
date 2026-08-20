<?php

declare(strict_types=1);

namespace App\Document\Service;

use App\Shared\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentUploadValidator
{
    private const int MAX_FILE_SIZE_BYTES = 10_485_760;
    private const array ALLOWED_MIME_TYPES = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'txt' => ['text/plain', 'text/x-plain'],
    ];

    public function validate(?UploadedFile $file): UploadedFile
    {
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException('Field "file" is required.');
        }

        if (!$file->isValid()) {
            throw new InvalidArgumentException('The uploaded file is invalid. Please try again.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (!array_key_exists($extension, self::ALLOWED_MIME_TYPES)) {
            throw new InvalidArgumentException('Unsupported file type. Please upload PDF, DOCX, or TXT.');
        }

        $size = $file->getSize();

        if ($size === false || $size <= 0) {
            throw new InvalidArgumentException('The uploaded file is empty.');
        }

        if ($size > self::MAX_FILE_SIZE_BYTES) {
            throw new InvalidArgumentException('File is too large. Maximum supported size is 10 MB.');
        }

        $mimeType = $file->getMimeType();

        if ($mimeType === null || !in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            throw new InvalidArgumentException('Uploaded file content does not match an allowed document type.');
        }

        return $file;
    }
}
