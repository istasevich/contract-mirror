<?php declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AnalyzeContractRequest
{
    public function __construct(
        #[Assert\NotNull]
        public ?UploadedFile $file,

        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public string $preferredLanguage = 'en',
    ) {
        // Nothing
    }
}
