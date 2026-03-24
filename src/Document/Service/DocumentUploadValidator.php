<?php declare(strict_types=1);

namespace App\Document\Service;

use App\Shared\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentUploadValidator
{
    private const int MAX_FILE_SIZE_BYTES = 10_485_760;
    private const array ALLOWED_EXTENSIONS = ['pdf', 'docx', 'txt'];

    public function validate(?UploadedFile $file): UploadedFile
    {
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException('Field "file" is required.');
        }

        if (!$file->isValid()) {
            throw new InvalidArgumentException('The uploaded file is invalid. Please try again.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Unsupported file type. Please upload PDF, DOCX, or TXT.');
        }

        if ($file->getSize() !== null && $file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new InvalidArgumentException('File is too large. Maximum supported size is 10 MB.');
        }

        return $file;
    }
}
