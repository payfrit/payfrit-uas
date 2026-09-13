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
    [Decimal::multiply('0.00000001','0.5'),'0.00000001'],
];
foreach($tests as [$actual,$expected]) if($actual!==$expected) throw new RuntimeException("Expected $expected, got $actual");
try { Decimal::normalize('123456789012345678901.00'); throw new RuntimeException('Magnitude limit was not enforced'); } catch (InvalidArgumentException) {}
try { Decimal::divide('1','0'); throw new RuntimeException('Division by zero was not rejected'); } catch (InvalidArgumentException) {}
echo count($tests)." UAS tests passed\n";
