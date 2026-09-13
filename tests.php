<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Uas/Decimal.php';
require_once __DIR__ . '/src/Uas/Api.php';

use Uas\Api;
use Uas\Decimal;

$passed = 0;

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    global $passed;
    if ($actual !== $expected) {
        throw new RuntimeException("{$label}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
    $passed++;
}

function assertInvalid(callable $operation, string $label): void
{
    global $passed;
    try {
        $operation();
    } catch (InvalidArgumentException) {
        $passed++;
        return;
    }

    throw new RuntimeException("{$label}: expected InvalidArgumentException");
}

function amountFromScaledInteger(int $value, int $scale): string
{
    $sign = $value < 0 ? '-' : '';
    $digits = str_pad((string) abs($value), $scale + 1, '0', STR_PAD_LEFT);

    return $sign . substr($digits, 0, -$scale) . '.' . substr($digits, -$scale);
}

assertSameValue('12.34000000', Decimal::normalize('12.34'), 'normalization');
assertSameValue('0.10000000', Decimal::normalize('.1'), 'shorthand normalization');
assertSameValue('-0.50000000', Decimal::normalize('-.5'), 'negative shorthand normalization');
assertSameValue('1.20000000', Decimal::normalize('+001.2'), 'canonical normalization');
assertSameValue('0.00000000', Decimal::normalize('-0'), 'negative zero normalization');
assertSameValue('99999999999999999999.99999999', Decimal::normalize('99999999999999999999.99999999'), 'maximum magnitude');

assertSameValue('12.34500000', Decimal::add('12.34000000', '0.00500000'), 'addition');
assertSameValue('-2.50000000', Decimal::subtract('10', '12.5'), 'subtraction');
assertSameValue('0.02000000', Decimal::multiply('.1', '.2'), 'exact multiplication');
assertSameValue('0.00000001', Decimal::multiply('0.00000001', '0.5'), 'positive half-up rounding');
assertSameValue('-0.00000001', Decimal::multiply('-0.00000001', '0.5'), 'negative half-up rounding');
assertSameValue('0.00000000', Decimal::multiply('-.1', '0'), 'arithmetic negative zero');
assertSameValue('0.33333333', Decimal::divide('1', '3'), 'division');
assertSameValue('0.16666667', Decimal::divide('1', '6'), 'division half-up rounding');
assertSameValue(-1, Decimal::compare('-1', '0'), 'negative comparison');
assertSameValue(0, Decimal::compare('1.0', '1.00000000'), 'equal comparison');
assertSameValue(1, Decimal::compare('2', '1.99999999'), 'positive comparison');

$grid = [-23456, -10001, -1, 0, 1, 9999, 12345];
foreach ($grid as $left) {
    foreach ($grid as $right) {
        $leftAmount = amountFromScaledInteger($left, 4);
        $rightAmount = amountFromScaledInteger($right, 4);
        assertSameValue(
            amountFromScaledInteger(($left + $right) * 10000, 8),
            Decimal::add($leftAmount, $rightAmount),
            "grid addition {$leftAmount} + {$rightAmount}"
        );
        assertSameValue(
            amountFromScaledInteger($left * $right, 8),
            Decimal::multiply($leftAmount, $rightAmount),
            "grid multiplication {$leftAmount} * {$rightAmount}"
        );

        if ($right !== 0) {
            $numerator = abs($left) * 100000000;
            $quotient = intdiv($numerator, abs($right));
            if (($numerator % abs($right)) * 2 >= abs($right)) {
                $quotient++;
            }
            if (($left < 0) xor ($right < 0)) {
                $quotient *= -1;
            }
            assertSameValue(
                amountFromScaledInteger($quotient, 8),
                Decimal::divide($leftAmount, $rightAmount),
                "grid division {$leftAmount} / {$rightAmount}"
            );
        }
    }
}

assertInvalid(fn() => Decimal::normalize('1e2'), 'exponential notation');
assertInvalid(fn() => Decimal::normalize('1.000000001'), 'excess precision');
assertInvalid(fn() => Decimal::normalize('123456789012345678901'), 'input overflow');
assertInvalid(fn() => Decimal::add('99999999999999999999.99999999', '0.00000001'), 'arithmetic overflow');
assertInvalid(fn() => Decimal::divide('1', '0'), 'division by zero');

assertSameValue(['amount' => '12.34000000'], Api::normalize(['amount' => '12.34']), 'API normalization');
assertInvalid(fn() => Api::normalize(['amount' => 12.34]), 'numeric JSON amount');
assertInvalid(fn() => Api::arithmetic(['amounts' => ['1', 2]], 'add'), 'numeric arithmetic operand');
assertInvalid(fn() => Api::arithmetic(['amounts' => ['1', '2', '3']], 'multiply'), 'extra binary operand');

$conversion = Api::convert([
    'amount' => '12.34',
    'rate' => '0.92',
    'sourceCurrency' => 'usd',
    'targetCurrency' => 'eur',
    'rateTimestamp' => '2026-09-12T00:00:00Z',
]);
assertSameValue('11.35280000', $conversion['amount'], 'conversion amount');
assertSameValue('0.92000000', $conversion['rate'], 'canonical conversion rate');
assertSameValue('USD', $conversion['sourceCurrency'], 'canonical source currency');
assertSameValue('EUR', $conversion['targetCurrency'], 'canonical target currency');
assertInvalid(fn() => Api::convert([
    'amount' => '1',
    'rate' => 1.2,
    'sourceCurrency' => 'USD',
    'targetCurrency' => 'EUR',
    'rateTimestamp' => '2026-09-12T00:00:00Z',
]), 'numeric conversion rate');
assertInvalid(fn() => Api::convert([
    'amount' => '1',
    'rate' => '0',
    'sourceCurrency' => 'USD',
    'targetCurrency' => 'EUR',
    'rateTimestamp' => '2026-09-12T00:00:00Z',
]), 'zero conversion rate');
assertInvalid(fn() => Api::convert([
    'amount' => '1',
    'rate' => '1',
    'sourceCurrency' => 'USD',
    'targetCurrency' => 'ZZZ',
    'rateTimestamp' => '2026-09-12T00:00:00Z',
]), 'unsupported currency');
assertInvalid(fn() => Api::convert([
    'amount' => '1',
    'rate' => '1',
    'sourceCurrency' => 'USD',
    'targetCurrency' => 'EUR',
    'rateTimestamp' => 'tomorrow',
]), 'non-RFC 3339 timestamp');
assertInvalid(fn() => Api::convert([
    'amount' => '1',
    'rate' => '1',
    'sourceCurrency' => 'USD',
    'targetCurrency' => 'EUR',
    'rateTimestamp' => '2026-02-31T00:00:00Z',
]), 'invalid calendar timestamp');

$currencies = array_column(Api::currencies(), null, 'code');
assertSameValue(31, count($currencies), 'currency catalog size');
assertSameValue(3, $currencies['KWD']['decimalPlaces'], 'three-decimal currency');
assertSameValue(0, $currencies['JPY']['decimalPlaces'], 'zero-decimal currency');
assertSameValue(2, $currencies['TWD']['decimalPlaces'], 'TWD decimal places');
assertSameValue('0.05', $currencies['CAD']['cashRounding'], 'CAD cash rounding');

$schema = json_decode(
    file_get_contents(__DIR__ . '/schema/universal-amount.schema.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$amountPattern = '~' . $schema['properties']['amount']['pattern'] . '~D';
assertSameValue(1, preg_match($amountPattern, '0.00000000'), 'schema accepts zero');
assertSameValue(1, preg_match($amountPattern, '-0.00000001'), 'schema accepts negative amount');
assertSameValue(0, preg_match($amountPattern, '-0.00000000'), 'schema rejects negative zero');
assertSameValue(0, preg_match($amountPattern, '100000000000000000000.00000000'), 'schema rejects overflow');

echo "{$passed} UAS tests passed\n";
