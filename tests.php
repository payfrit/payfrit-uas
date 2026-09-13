<?php
declare(strict_types=1);
require_once __DIR__ . '/src/Uas/Decimal.php';
use Uas\Decimal;
$tests=[
    [Decimal::normalize('12.34'),'12.34000000'],
    [Decimal::add('12.34000000','0.00500000'),'12.34500000'],
    [Decimal::subtract('10.00000000','12.50000000'),'-2.50000000'],
    [Decimal::multiply('12.34000000','1.10000000'),'13.57400000'],
    [Decimal::divide('1.00000000','3.00000000'),'0.33333333'],
    [Decimal::divide('-10.00000000','4.00000000'),'-2.50000000'],
];
foreach($tests as [$actual,$expected]) if($actual!==$expected) throw new RuntimeException("Expected $expected, got $actual");
echo count($tests)." UAS tests passed\n";
