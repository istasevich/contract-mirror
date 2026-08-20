<?php

declare(strict_types=1);

namespace App\Tests\AI;

use App\AI\Response\LlmJsonResponseDecoder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LlmJsonResponseDecoderTest extends TestCase
{
    public function testDecodesJsonObject(): void
    {
        self::assertSame(['risk_score' => 42], (new LlmJsonResponseDecoder())->decode('{"risk_score":42}'));
    }

    public function testRejectsMalformedJson(): void
    {
        $this->expectException(RuntimeException::class);

        (new LlmJsonResponseDecoder())->decode('{broken');
    }
}
