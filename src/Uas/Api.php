<?php
declare(strict_types=1);

namespace Uas;

use InvalidArgumentException;

final class Api
{
    public const VERSION = '0.0.2';
    private const RFC3339_PATTERN = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(?:Z|[+-](\d{2}):(\d{2}))$/D';

    public static function currencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'United States dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'EUR', 'name' => 'Euro', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'GBP', 'name' => 'Pound sterling', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'CAD', 'name' => 'Canadian dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.05'],
            ['code' => 'AUD', 'name' => 'Australian dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'NZD', 'name' => 'New Zealand dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'CHF', 'name' => 'Swiss franc', 'decimalPlaces' => 2, 'cashRounding' => '0.05'],
            ['code' => 'CNY', 'name' => 'Renminbi yuan', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'HKD', 'name' => 'Hong Kong dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'SGD', 'name' => 'Singapore dollar', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'KRW', 'name' => 'South Korean won', 'decimalPlaces' => 0, 'cashRounding' => '1'],
            ['code' => 'INR', 'name' => 'Indian rupee', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'PHP', 'name' => 'Philippine peso', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'MXN', 'name' => 'Mexican peso', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'BRL', 'name' => 'Brazilian real', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'ZAR', 'name' => 'South African rand', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'SEK', 'name' => 'Swedish krona', 'decimalPlaces' => 2, 'cashRounding' => '1'],
            ['code' => 'NOK', 'name' => 'Norwegian krone', 'decimalPlaces' => 2, 'cashRounding' => '1'],
            ['code' => 'DKK', 'name' => 'Danish krone', 'decimalPlaces' => 2, 'cashRounding' => '0.50'],
            ['code' => 'PLN', 'name' => 'Polish zloty', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'CZK', 'name' => 'Czech koruna', 'decimalPlaces' => 2, 'cashRounding' => '1'],
            ['code' => 'TRY', 'name' => 'Turkish lira', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'ILS', 'name' => 'Israeli new shekel', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'AED', 'name' => 'United Arab Emirates dirham', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'SAR', 'name' => 'Saudi riyal', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'JPY', 'name' => 'Japanese yen', 'decimalPlaces' => 0, 'cashRounding' => '1'],
            ['code' => 'THB', 'name' => 'Thai baht', 'decimalPlaces' => 2, 'cashRounding' => '0.01'],
            ['code' => 'IDR', 'name' => 'Indonesian rupiah', 'decimalPlaces' => 0, 'cashRounding' => '1'],
            ['code' => 'VND', 'name' => 'Vietnamese dong', 'decimalPlaces' => 0, 'cashRounding' => '1'],
            ['code' => 'TWD', 'name' => 'New Taiwan dollar', 'decimalPlaces' => 2, 'cashRounding' => '1'],
            ['code' => 'KWD', 'name' => 'Kuwaiti dinar', 'decimalPlaces' => 3, 'cashRounding' => '0.001'],
        ];
    }

    public static function normalize(array $body): array
    {
        return ['amount' => Decimal::normalize(self::requiredString($body, 'amount'))];
    }

    public static function arithmetic(array $body, string $operation = 'add'): array
    {
        $values = $body['amounts'] ?? [];
        $minimum = $operation === 'add' ? 1 : 2;
        if (!is_array($values) || count($values) < $minimum) {
            throw new InvalidArgumentException('amounts must contain enough values for the operation');
        }
        if ($operation !== 'add' && count($values) !== 2) {
            throw new InvalidArgumentException($operation . ' accepts exactly two amounts');
        }
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('amounts must be decimal strings');
            }
        }

        $left = $values[0];
        $right = $values[1] ?? '0';
        $total = match ($operation) {
            'add' => array_reduce($values, static fn(string $carry, string $value): string => Decimal::add($carry, $value), '0'),
            'subtract' => Decimal::subtract($left, $right),
            'multiply' => Decimal::multiply($left, $right),
            'divide' => Decimal::divide($left, $right),
            default => throw new InvalidArgumentException('unsupported arithmetic operation'),
        };

        return ['amount' => Decimal::normalize($total)];
    }

    public static function convert(array $body): array
    {
        $amount = Decimal::normalize(self::requiredString($body, 'amount'));
        $rate = Decimal::normalize(self::requiredString($body, 'rate'));
        $source = strtoupper(trim(self::requiredString($body, 'sourceCurrency')));
        $target = strtoupper(trim(self::requiredString($body, 'targetCurrency')));
        $codes = array_column(self::currencies(), 'code');
        if (!in_array($source, $codes, true) || !in_array($target, $codes, true)) {
            throw new InvalidArgumentException('sourceCurrency and targetCurrency must be supported currency codes');
        }
        if (Decimal::compare($rate, '0') <= 0) {
            throw new InvalidArgumentException('rate must be greater than zero');
        }

        $timestamp = trim(self::requiredString($body, 'rateTimestamp'));
        if (!self::isRfc3339($timestamp)) {
            throw new InvalidArgumentException('rateTimestamp must be an RFC 3339 timestamp');
        }

        return [
            'amount' => Decimal::multiply($amount, $rate),
            'sourceCurrency' => $source,
            'targetCurrency' => $target,
            'rate' => $rate,
            'rateTimestamp' => $timestamp,
        ];
    }

    private static function requiredString(array $body, string $field): string
    {
        if (!isset($body[$field]) || !is_string($body[$field])) {
            throw new InvalidArgumentException($field . ' must be a string');
        }

        return $body[$field];
    }

    private static function isRfc3339(string $timestamp): bool
    {
        if (preg_match(self::RFC3339_PATTERN, $timestamp, $parts) !== 1) {
            return false;
        }

        $validOffset = !isset($parts[7])
            || ((int) $parts[7] <= 23 && (int) $parts[8] <= 59);

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            && (int) $parts[4] <= 23
            && (int) $parts[5] <= 59
            && (int) $parts[6] <= 60
            && $validOffset;
    }
}
