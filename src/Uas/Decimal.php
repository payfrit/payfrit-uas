<?php
declare(strict_types=1);

namespace Uas;

use InvalidArgumentException;

final class Decimal
{
    public const SCALE = 8;

    public static function normalize(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^([+-]?)(?:(\d+)(?:\.(\d+))?|\.(\d+))$/', $value, $m)) {
            throw new InvalidArgumentException('amount must be a decimal string');
        }
        $integerPart = $m[2] ?? '';
        $fraction = ($m[3] ?? '') !== '' ? $m[3] : ($m[4] ?? '');
        if (strlen($fraction) > self::SCALE) {
            throw new InvalidArgumentException('amount supports at most 8 fractional places');
        }
        $integer = ltrim($integerPart, '0') ?: '0';
        if (strlen($integer) > 20) {
            throw new InvalidArgumentException('amount exceeds DECIMAL(28,8) magnitude');
        }
        $fraction = str_pad($fraction, self::SCALE, '0');
        $negative = $m[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '');
        return ($negative ? '-' : '') . $integer . '.' . $fraction;
    }

    public static function add(string $left, string $right): string
    {
        [$a, $b] = self::align($left, $right);
        if ($a[0] === $b[0]) {
            return self::fromInteger(
                self::addIntegers(substr($a, 1), substr($b, 1)),
                $a[0] === '-' ? '-' : ''
            );
        }

        $comparison = self::compareIntegers(substr($a, 1), substr($b, 1));
        if ($comparison === 0) {
            return self::zero();
        }
        if ($comparison > 0) {
            return self::fromInteger(
                self::subtractIntegers(substr($a, 1), substr($b, 1)),
                $a[0] === '-' ? '-' : ''
            );
        }

        return self::fromInteger(
            self::subtractIntegers(substr($b, 1), substr($a, 1)),
            $b[0] === '-' ? '-' : ''
        );
    }

    public static function subtract(string $left, string $right): string
    {
        $right = self::normalize($right);
        $inverse = ($right[0] === '-' ? '' : '-') . ltrim($right, '+-');

        return self::add($left, $inverse);
    }

    public static function multiply(string $left, string $right): string
    {
        $a = self::normalize($left);
        $b = self::normalize($right);
        $negative = (($a[0] === '-') xor ($b[0] === '-'));
        $product = self::multiplyIntegers(str_replace(['-', '.'], '', $a), str_replace(['-', '.'], '', $b));
        $scaleFactor = '1' . str_repeat('0', self::SCALE);
        [$product, $remainder] = self::divideIntegers($product, $scaleFactor);
        if (self::compareIntegers(self::multiplyIntegers($remainder, '2'), $scaleFactor) >= 0) {
            $product = self::addIntegers($product, '1');
        }

        return self::fromInteger($product, $negative ? '-' : '');
    }

    public static function divide(string $left, string $right): string
    {
        $a = self::normalize($left);
        $b = self::normalize($right);
        $bDigits = str_replace(['-', '.'], '', $b);
        if (trim($bDigits, '0') === '') {
            throw new InvalidArgumentException('division by zero');
        }

        $negative = (($a[0] === '-') xor ($b[0] === '-'));
        $numerator = str_replace(['-', '.'], '', $a) . str_repeat('0', self::SCALE);
        [$quotient, $remainder] = self::divideIntegers($numerator, $bDigits);
        if (self::compareIntegers(self::multiplyIntegers($remainder, '2'), $bDigits) >= 0) {
            $quotient = self::addIntegers($quotient, '1');
        }

        return self::fromInteger($quotient, $negative ? '-' : '');
    }

    public static function compare(string $left, string $right): int
    {
        [$a, $b] = self::align($left, $right);
        if ($a[0] !== $b[0]) {
            return $a[0] === '-' ? -1 : 1;
        }

        $comparison = self::compareIntegers(substr($a, 1), substr($b, 1));

        return $a[0] === '-' ? -$comparison : $comparison;
    }

    private static function align(string $left, string $right): array
    {
        $a = self::normalize($left);
        $b = self::normalize($right);
        $aSign = str_starts_with($a, '-') ? '-' : '+';
        $bSign = str_starts_with($b, '-') ? '-' : '+';

        return [$aSign . str_replace(['-', '.'], '', $a), $bSign . str_replace(['-', '.'], '', $b)];
    }

    private static function fromInteger(string $digits, string $sign): string
    {
        $digits = ltrim($digits, '0') ?: '0';
        $isZero = $digits === '0';
        $integerDigits = max(1, strlen($digits) - self::SCALE);
        if ($integerDigits > 20) {
            throw new InvalidArgumentException('amount exceeds DECIMAL(28,8) magnitude');
        }

        $digits = str_pad($digits, self::SCALE + 1, '0', STR_PAD_LEFT);
        $amount = substr($digits, 0, -self::SCALE) . '.' . substr($digits, -self::SCALE);

        return ($sign === '-' && !$isZero) ? '-' . $amount : $amount;
    }

    private static function zero(): string
    {
        return '0.' . str_repeat('0', self::SCALE);
    }

    private static function compareIntegers(string $a, string $b): int
    {
        $a = ltrim($a, '0') ?: '0';
        $b = ltrim($b, '0') ?: '0';

        return strlen($a) <=> strlen($b) ?: strcmp($a, $b) <=> 0;
    }

    private static function addIntegers(string $a, string $b): string
    {
        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        $carry = 0;
        $output = '';

        while ($i >= 0 || $j >= 0 || $carry) {
            $digit = ($i >= 0 ? (int) $a[$i--] : 0)
                + ($j >= 0 ? (int) $b[$j--] : 0)
                + $carry;
            $output = ($digit % 10) . $output;
            $carry = intdiv($digit, 10);
        }

        return $output;
    }

    private static function subtractIntegers(string $a, string $b): string
    {
        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        $borrow = 0;
        $output = '';

        while ($i >= 0) {
            $digit = (int) $a[$i--] - $borrow - ($j >= 0 ? (int) $b[$j--] : 0);
            if ($digit < 0) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $output = $digit . $output;
        }

        return ltrim($output, '0') ?: '0';
    }

    private static function multiplyIntegers(string $a, string $b): string
    {
        $result = array_fill(0, strlen($a) + strlen($b), 0);

        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            for ($j = strlen($b) - 1; $j >= 0; $j--) {
                $position = $i + $j + 1;
                $value = (int) $a[$i] * (int) $b[$j] + $result[$position];
                $result[$position] = $value % 10;
                $result[$position - 1] += intdiv($value, 10);
            }
        }

        return ltrim(implode('', $result), '0') ?: '0';
    }

    private static function divideIntegers(string $numerator, string $denominator): array
    {
        $quotient = '';
        $remainder = '0';

        foreach (str_split($numerator) as $digit) {
            $remainder = ltrim($remainder . $digit, '0') ?: '0';
            $quotientDigit = 0;
            while (self::compareIntegers($remainder, $denominator) >= 0) {
                $remainder = self::subtractIntegers($remainder, $denominator);
                $quotientDigit++;
            }
            $quotient .= (string) $quotientDigit;
        }

        return [ltrim($quotient, '0') ?: '0', $remainder];
    }
}
