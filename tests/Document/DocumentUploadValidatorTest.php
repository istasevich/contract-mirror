<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Service\DocumentUploadValidator;
use App\Shared\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentUploadValidatorTest extends TestCase
{
    public function testAcceptsPlainTextUpload(): void
    {
        $file = $this->uploadedFile('contract.txt', 'Hello contract');

        self::assertSame($file, (new DocumentUploadValidator())->validate($file));
    }

    public function testRejectsInvalidMimeEvenWhenExtensionIsAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DocumentUploadValidator())->validate($this->uploadedFile('contract.pdf', 'not a pdf'));
    }

    public function testRejectsEmptyFile(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DocumentUploadValidator())->validate($this->uploadedFile('contract.txt', ''));
    }

    private function uploadedFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cm-upload-');
        self::assertIsString($path);
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }
}
