<?php
declare(strict_types=1);

namespace Uas;

final class Api
{
    public static function currencies(): array
    {
        return [
            ['code'=>'USD','name'=>'United States dollar','decimalPlaces'=>2,'cashRounding'=>'0.01'],
            ['code'=>'EUR','name'=>'Euro','decimalPlaces'=>2,'cashRounding'=>'0.01'],
            ['code'=>'GBP','name'=>'Pound sterling','decimalPlaces'=>2,'cashRounding'=>'0.01'],
            ['code'=>'JPY','name'=>'Japanese yen','decimalPlaces'=>0,'cashRounding'=>'1'],
            ['code'=>'KWD','name'=>'Kuwaiti dinar','decimalPlaces'=>3,'cashRounding'=>'0.001'],
        ];
    }
    public static function normalize(array $body): array { return ['amount'=>Decimal::normalize((string)($body['amount'] ?? ''))]; }
    public static function arithmetic(array $body, string $operation = 'add'): array
    {
        $values = $body['amounts'] ?? [];
        if (!is_array($values) || count($values) < ($operation === 'add' ? 1 : 2)) throw new \InvalidArgumentException('amounts must contain enough values for the operation');
        $left = (string)$values[0]; $right = (string)($values[1] ?? '0');
        $total = match ($operation) {
            'add' => array_reduce($values, static fn(string $carry, mixed $value): string => Decimal::add($carry, (string)$value), '0'),
            'subtract' => Decimal::subtract($left, $right),
            'multiply' => Decimal::multiply($left, $right),
            'divide' => Decimal::divide($left, $right),
            default => throw new \InvalidArgumentException('unsupported arithmetic operation'),
        };
        return ['amount'=>Decimal::normalize($total)];
    }
    public static function convert(array $body): array
    {
        $amount=Decimal::normalize((string)($body['amount']??'')); $rate=Decimal::normalize((string)($body['rate']??''));
        return ['amount'=>Decimal::multiply($amount,$rate),'sourceCurrency'=>(string)($body['sourceCurrency']??''),'targetCurrency'=>(string)($body['targetCurrency']??''),'rate'=>(string)$rate,'rateTimestamp'=>(string)($body['rateTimestamp']??'')];
    }
}
