<?php

declare(strict_types=1);

namespace App\Shared\Decimal;

use App\Shared\Exception\InvalidArgumentException;

final class Decimal
{
    public static function toMinorUnits(string $amount, int $scale): string
    {
        $amount = trim($amount);

        if (preg_match('/^\d+(?:\.\d+)?$/', $amount) !== 1) {
            throw new InvalidArgumentException('Invalid decimal amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);
        $minor = ltrim($whole . $fraction, '0');

        return $minor === '' ? '0' : $minor;
    }

    public static function compareIntegerStrings(string $left, string $right): int
    {
        $left = ltrim($left, '0');
        $right = ltrim($right, '0');
        $left = $left === '' ? '0' : $left;
        $right = $right === '' ? '0' : $right;

        if (strlen($left) !== strlen($right)) {
            return strlen($left) <=> strlen($right);
        }

        return $left <=> $right;
    }
}
