<?php
declare(strict_types=1);

namespace Uas;

use InvalidArgumentException;

final class Decimal
{
    public const SCALE = 8;

    public static function normalize(string $value, int $scale = self::SCALE): string
    {
        $value = trim($value);
        if (!preg_match('/^([+-]?)(?:(\d+)(?:\.(\d+))?|\.(\d+))$/', $value, $m)) {
            throw new InvalidArgumentException('amount must be a decimal string');
        }
        $integerPart = $m[2] ?? '';
        $fraction = ($m[3] ?? '') !== '' ? $m[3] : ($m[4] ?? '');
        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException("amount supports at most {$scale} fractional places");
        }
        $integer = ltrim($integerPart, '0') ?: '0';
        if (strlen($integer) > 20) {
            throw new InvalidArgumentException('amount exceeds DECIMAL(28,8) magnitude');
        }
        $fraction = str_pad($fraction, $scale, '0');
        $negative = $m[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '');
        return ($negative ? '-' : '') . $integer . '.' . $fraction;
    }

    public static function add(string $left, string $right, int $scale = self::SCALE): string
    {
        [$a, $b] = self::align($left, $right, $scale);
        if ($a[0] === $b[0]) return self::fromInteger(self::addIntegers(substr($a, 1), substr($b, 1)), $a[0] === '-' ? '-' : '', $scale);
        $cmp = self::compareIntegers(substr($a, 1), substr($b, 1));
        if ($cmp === 0) return self::zero($scale);
        if ($cmp > 0) return self::fromInteger(self::subtractIntegers(substr($a, 1), substr($b, 1)), $a[0] === '-' ? '-' : '', $scale);
        return self::fromInteger(self::subtractIntegers(substr($b, 1), substr($a, 1)), $b[0] === '-' ? '-' : '', $scale);
    }

    public static function subtract(string $left, string $right, int $scale = self::SCALE): string
    {
        $right = self::normalize($right, $scale);
        return self::add($left, ($right[0] === '-' ? '' : '-') . ltrim($right, '+-'), $scale);
    }

    public static function multiply(string $left, string $right, int $scale = self::SCALE): string
    {
        $a = self::normalize($left, $scale);
        $b = self::normalize($right, $scale);
        $negative = (($a[0] === '-') xor ($b[0] === '-'));
        $product = self::multiplyIntegers(str_replace(['-', '.'], '', $a), str_replace(['-', '.'], '', $b));
        [$product, $remainder] = self::divideIntegers($product, '1' . str_repeat('0', $scale));
        if (self::compareIntegers(self::multiplyIntegers($remainder, '2'), '1' . str_repeat('0', $scale)) >= 0) {
            $product = self::addIntegers($product, '1');
        }
        return self::fromInteger($product, $negative ? '-' : '', $scale);
    }

    public static function divide(string $left, string $right, int $scale = self::SCALE): string
    {
        $a = self::normalize($left, $scale); $b = self::normalize($right, $scale);
        $bDigits = str_replace(['-', '.'], '', $b);
        if (trim($bDigits, '0') === '') throw new InvalidArgumentException('division by zero');
        $negative = (($a[0] === '-') xor ($b[0] === '-'));
        [$quotient, $remainder] = self::divideIntegers(str_replace(['-', '.'], '', $a) . str_repeat('0', $scale), $bDigits);
        if (self::compareIntegers(self::multiplyIntegers($remainder, '2'), $bDigits) >= 0) {
            $quotient = self::addIntegers($quotient, '1');
        }
        return self::fromInteger($quotient, $negative ? '-' : '', $scale);
    }

    public static function compare(string $left, string $right, int $scale = self::SCALE): int
    {
        [$a, $b] = self::align($left, $right, $scale);
        if ($a[0] !== $b[0]) return $a[0] === '-' ? -1 : 1;
        $cmp = self::compareIntegers(substr($a, 1), substr($b, 1));
        return $a[0] === '-' ? -$cmp : $cmp;
    }

    private static function align(string $left, string $right, int $scale): array
    {
        $a = self::normalize($left, $scale); $b = self::normalize($right, $scale);
        $aSign = str_starts_with($a, '-') ? '-' : '+';
        $bSign = str_starts_with($b, '-') ? '-' : '+';
        return [$aSign . str_replace(['-', '.'], '', $a), $bSign . str_replace(['-', '.'], '', $b)];
    }
    private static function fromInteger(string $digits, string $sign, int $scale): string
    {
        $digits = ltrim($digits, '0') ?: '0'; $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
        $out = substr($digits, 0, -$scale) . '.' . substr($digits, -$scale);
        return ($sign === '-' && $digits !== '0') ? '-' . $out : $out;
    }
    private static function zero(int $scale): string { return '0.' . str_repeat('0', $scale); }
    private static function compareIntegers(string $a, string $b): int { $a=ltrim($a,'0')?:'0'; $b=ltrim($b,'0')?:'0'; return strlen($a)<=>strlen($b) ?: strcmp($a,$b)<=>0; }
    private static function addIntegers(string $a, string $b): string { $i=strlen($a)-1;$j=strlen($b)-1;$carry=0;$o=''; while($i>=0||$j>=0||$carry){$n=($i>=0?(int)$a[$i--]:0)+($j>=0?(int)$b[$j--]:0)+$carry;$o=($n%10).$o;$carry=intdiv($n,10);} return $o; }
    private static function subtractIntegers(string $a, string $b): string { $i=strlen($a)-1;$j=strlen($b)-1;$borrow=0;$o=''; while($i>=0){$n=(int)$a[$i--]-$borrow-($j>=0?(int)$b[$j--]:0);if($n<0){$n+=10;$borrow=1;}else{$borrow=0;}$o=$n.$o;} return ltrim($o,'0')?:'0'; }
    private static function multiplyIntegers(string $a, string $b): string { $r=array_fill(0,strlen($a)+strlen($b),0); for($i=strlen($a)-1;$i>=0;$i--)for($j=strlen($b)-1;$j>=0;$j--){$p=$i+$j+1;$v=(int)$a[$i]*(int)$b[$j]+$r[$p];$r[$p]=$v%10;$r[$p-1]+=(int)($v/10);} return ltrim(implode('',$r),'0')?:'0'; }
    private static function divideIntegers(string $numerator, string $denominator): array { $q=''; $remainder='0'; foreach(str_split($numerator) as $digit){$remainder=ltrim($remainder.$digit,'0')?:'0';$n=0;while(self::compareIntegers($remainder,$denominator)>=0){$remainder=self::subtractIntegers($remainder,$denominator);$n++;} $q.=(string)$n;} return [ltrim($q,'0')?:'0',$remainder]; }
}
