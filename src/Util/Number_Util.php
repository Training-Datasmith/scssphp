<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Util;

use Scss_Php\Scss_Php\Value\Sass_Number;
/**
 * Utilities to deal with numbers with fuzziness for the Sass precision
 *
 * @internal
 */
final class Number_Util
{
    /**
     * The power of ten to which to round Sass numbers to determine if they're
     * fuzzy equal to one another
     *
     * This is also the minimum distance such that `a - b > EPSILON` implies that
     * `a` isn't fuzzy-equal to `b`. Note that the inverse implication is not
     * necessarily true! For example, if `a = 5.1e-11` and `b = 4.4e-11`, then
     * `a - b < 1e-11` but `a` fuzzy-equals 5e-11 and b fuzzy-equals 4e-11.
     *
     * @see https://github.com/sass/sass/blob/main/spec/types/number.md#fuzzy-equality
     */
    private const EPSILON = 10 ** (-Sass_Number::PRECISION - 1);
    private const INVERSE_EPSILON = 10 ** (Sass_Number::PRECISION + 1);
    public static function clamp(float $value, float $lower_limit, float $upper_limit): float
    {
        if ($value < $lower_limit) {
            return $lower_limit;
        }
        if ($value > $upper_limit) {
            return $upper_limit;
        }
        return $value;
    }
    public static function fuzzy_equals(float $number1, float $number2): bool
    {
        if ($number1 == $number2) {
            return true;
        }
        return abs($number1 - $number2) <= self::EPSILON && round($number1 * self::INVERSE_EPSILON) === round($number2 * self::INVERSE_EPSILON);
    }
    public static function fuzzy_less_than(float $number1, float $number2): bool
    {
        return $number1 < $number2 && !self::fuzzy_equals($number1, $number2);
    }
    public static function fuzzy_less_than_or_equals(float $number1, float $number2): bool
    {
        return $number1 <= $number2 || self::fuzzy_equals($number1, $number2);
    }
    public static function fuzzy_greater_than(float $number1, float $number2): bool
    {
        return $number1 > $number2 && !self::fuzzy_equals($number1, $number2);
    }
    public static function fuzzy_greater_than_or_equals(float $number1, float $number2): bool
    {
        return $number1 >= $number2 || self::fuzzy_equals($number1, $number2);
    }
    public static function fuzzy_is_int(float $number): bool
    {
        if (is_infinite($number) || is_nan($number)) {
            return false;
        }
        return self::fuzzy_equals($number, round($number));
    }
    public static function fuzzy_as_int(float $number): ?int
    {
        if (is_infinite($number) || is_nan($number)) {
            return null;
        }
        if ($number > \PHP_INT_MAX || $number < \PHP_INT_MIN) {
            return null;
        }
        $rounded = (int) round($number);
        return self::fuzzy_equals($number, $rounded) ? $rounded : null;
    }
    public static function fuzzy_round(float $number): int
    {
        if ($number > 0) {
            return intval(self::fuzzy_less_than(fmod($number, 1), 0.5) ? floor($number) : ceil($number));
        }
        return intval(self::fuzzy_less_than_or_equals(fmod($number, 1), 0.5) ? floor($number) : ceil($number));
    }
    public static function fuzzy_check_range(float $number, float $min, float $max): ?float
    {
        if (self::fuzzy_equals($number, $min)) {
            return $min;
        }
        if (self::fuzzy_equals($number, $max)) {
            return $max;
        }
        if ($number > $min && $number < $max) {
            return $number;
        }
        return null;
    }
    /**
     * @throws \OutOfRangeException
     */
    public static function fuzzy_assert_range(float $number, float $min, float $max, ?string $name = null): float
    {
        $result = self::fuzzy_check_range($number, $min, $max);
        if (!\is_null($result)) {
            return $result;
        }
        $name_display = $name ? " {$name}" : '';
        throw new \OutOfRangeException("Invalid value:{$name_display} must be between {$min} and {$max}: {$number}.");
    }
    /**
     * Returns $num1 / $num2, using Sass's division semantic.
     *
     * Sass allows dividing by 0.
     */
    public static function divide_like_sass(float $num1, float $num2): float
    {
        if ($num2 == 0) {
            if ($num1 == 0) {
                return NAN;
            }
            if ($num1 > 0) {
                return INF;
            }
            return -INF;
        }
        return $num1 / $num2;
    }
    /**
     * Return $num1 modulo $num2, using Sass's [floored division] modulo
     * semantics, which it inherited from Ruby and which differ from Dart's.
     *
     * [floored division]: https://en.wikipedia.org/wiki/Modulo_operation#Variants_of_the_definition
     */
    public static function modulo_like_sass(float $num1, float $num2): float
    {
        if (is_infinite($num1)) {
            return NAN;
        }
        if (is_infinite($num2)) {
            return self::sign_including_zero($num1) === self::sign($num2) ? $num1 : NAN;
        }
        if ($num2 == 0) {
            return NAN;
        }
        $result = fmod($num1, $num2);
        if ($result == 0) {
            return 0;
        }
        // PHP's fdiv has a different semantic when the 2 numbers have a different sign.
        if ($num2 < 0 xor $num1 < 0) {
            $result += $num2;
        }
        return $result;
    }
    public static function sqrt(Sass_Number $number): Sass_Number
    {
        $number->assert_no_units('number');
        return Sass_Number::create(sqrt($number->get_value()));
    }
    public static function sin(Sass_Number $number): Sass_Number
    {
        return Sass_Number::create(sin($number->coerce_value_to_unit('rad', 'number')));
    }
    public static function cos(Sass_Number $number): Sass_Number
    {
        return Sass_Number::create(cos($number->coerce_value_to_unit('rad', 'number')));
    }
    public static function tan(Sass_Number $number): Sass_Number
    {
        return Sass_Number::create(tan($number->coerce_value_to_unit('rad', 'number')));
    }
    public static function atan(Sass_Number $number): Sass_Number
    {
        $number->assert_no_units('number');
        return self::radians_to_degrees(atan($number->get_value()));
    }
    public static function asin(Sass_Number $number): Sass_Number
    {
        $number->assert_no_units('number');
        return self::radians_to_degrees(asin($number->get_value()));
    }
    public static function acos(Sass_Number $number): Sass_Number
    {
        $number->assert_no_units('number');
        return self::radians_to_degrees(acos($number->get_value()));
    }
    public static function abs(Sass_Number $number): Sass_Number
    {
        return Sass_Number::create(abs($number->get_value()))->coerce_to_match($number);
    }
    public static function log(Sass_Number $number, ?Sass_Number $base): Sass_Number
    {
        if ($base !== null) {
            return Sass_Number::create(self::divide_like_sass(log($number->get_value()), log($base->get_value())));
        }
        return Sass_Number::create(log($number->get_value()));
    }
    public static function pow(Sass_Number $base, Sass_Number $exponent): Sass_Number
    {
        $base->assert_no_units('base');
        $exponent->assert_no_units('exponent');
        if (\PHP_VERSION_ID >= 80400) {
            $value = fpow($base->get_value(), $exponent->get_value());
        } else {
            $value = $base->get_value() ** $exponent->get_value();
        }
        return Sass_Number::create($value);
    }
    public static function atan2(Sass_Number $y, Sass_Number $x): Sass_Number
    {
        return self::radians_to_degrees(atan2($y->get_value(), $x->convert_value_to_match($y, 'x', 'y')));
    }
    private static function radians_to_degrees(float $radians): Sass_Number
    {
        return Sass_Number::with_units($radians * (180 / \M_PI), ['deg']);
    }
    public static function sign(float $num): int
    {
        if ($num > 0) {
            return 1;
        }
        if ($num < 0) {
            return -1;
        }
        return 0;
    }
    public static function sign_including_zero(float $num): int
    {
        // In PHP, negative 0 and positive 0 are equal even for strict equality, so we need a different detection
        if ($num === 0.0) {
            if ('-0' === (string) $num) {
                return -1;
            }
            return 1;
        }
        return self::sign($num);
    }
}