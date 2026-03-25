<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AnalyzeContractRequest
{
    public function __construct(
        #[Assert\NotNull]
        public ?UploadedFile $file,

        #[Assert\NotBlank]
        #[Assert\Length(max: 16)]
        public string $preferredLanguage = 'English',
    ) {
        // Nothing
    }

    public static function fromRequest(Request $request): self
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('contractFile');

        $preferredLanguage = (string) $request->request->get('preferredLanguage', 'English');

        return new self(
            file: $file,
            preferredLanguage: trim($preferredLanguage) !== '' ? $preferredLanguage : 'English',
        );
    }
}
