<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Enum\DocumentTypeEnum;
use App\Document\Parser\DocumentTextExtractorInterface;
use App\Document\Service\DocumentTextExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentTextExtractorTest extends TestCase
{
    public function testSelectsFirstSupportingExtractorAndNormalizesText(): void
    {
        $extractor = new DocumentTextExtractor([
            new class () implements DocumentTextExtractorInterface {
                public function supports(UploadedFile $file): bool
                {
                    return false;
                }

                public function extract(UploadedFile $file): string
                {
                    return 'wrong';
                }
            },
            new class () implements DocumentTextExtractorInterface {
                public function supports(UploadedFile $file): bool
                {
                    return true;
                }

                public function extract(UploadedFile $file): string
                {
                    return "Hello   contract\n\n\nWorld";
                }
            },
        ]);

        $content = $extractor->extract($this->uploadedFile('contract.txt', 'ignored'));

        self::assertSame(DocumentTypeEnum::TXT, $content->documentType);
        self::assertSame("Hello contract\n\nWorld", $content->content);
    }

    public function testRejectsCorruptedOrUnreadableContent(): void
    {
        $extractor = new DocumentTextExtractor([
            new class () implements DocumentTextExtractorInterface {
                public function supports(UploadedFile $file): bool
                {
                    return true;
                }

                public function extract(UploadedFile $file): string
                {
                    return '';
                }
            },
        ]);

        $this->expectException(RuntimeException::class);

        $extractor->extract($this->uploadedFile('contract.txt', 'ignored'));
    }

    private function uploadedFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cm-extract-');
        self::assertIsString($path);
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }
}
