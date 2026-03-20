<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Function;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Warn;
/**
 * @internal
 */
final class Math_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function abs(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Number
    {
        $number = $arguments[0]->assert_number('number');
        // TODO implement the deprecation for the % unit once modules are implemented to provided the replacement
        return Sass_Number::with_units(abs($number->get_value()), $number->get_numerator_units(), $number->get_denominator_units());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function ceil(array $arguments): Value
    {
        return self::number_function($arguments, ceil(...));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function floor(array $arguments): Value
    {
        return self::number_function($arguments, floor(...));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function max(array $arguments): Value
    {
        $max = null;
        foreach ($arguments[0]->as_list() as $value) {
            $number = $value->assert_number();
            if ($max === null || $max->less_than($number)->is_truthy()) {
                $max = $number;
            }
        }
        if ($max !== null) {
            return $max;
        }
        throw new Sass_Script_Exception('At least one argument must be passed.');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function min(array $arguments): Value
    {
        $min = null;
        foreach ($arguments[0]->as_list() as $value) {
            $number = $value->assert_number();
            if ($min === null || $min->greater_than($number)->is_truthy()) {
                $min = $number;
            }
        }
        if ($min !== null) {
            return $min;
        }
        throw new Sass_Script_Exception('At least one argument must be passed.');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function round(array $arguments): Value
    {
        return self::number_function($arguments, round(...));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function compatible(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        $number1 = $arguments[0]->assert_number('number1');
        $number2 = $arguments[1]->assert_number('number2');
        return Sass_Boolean::create($number1->is_comparable_to($number2));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function is_unitless(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        $number = $arguments[0]->assert_number('number');
        return Sass_Boolean::create(!$number->has_units());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function unit(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $number = $arguments[0]->assert_number('number');
        return new Sass_String($number->get_unit_string(), true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function percentage(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Number
    {
        $number = $arguments[0]->assert_number('number');
        $number->assert_no_units('number');
        return Sass_Number::create($number->get_value() * 100, '%');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function random(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Number
    {
        if ($arguments[0] instanceof Sass_Null) {
            // TODO use a better algorithm to generate a random float.
            $max = mt_getrandmax();
            return Sass_Number::create(mt_rand(0, $max - 1) / $max);
        }
        $limit = $arguments[0]->assert_number('limit');
        if ($limit->has_units()) {
            $unit_string = $limit->get_unit_string();
            // TODO update the message when implementing modules and deprecating division.
            Warn::for_deprecation(<<<TXT
            random() will no longer ignore \$limit units ({$limit}) in a future release.
            
            Recommendation: random(\$limit / 1{$unit_string}) * 1{$unit_string}
            
            To preserve current behavior: random(\$limit / 1{$unit_string})
            
            More info: https://sass-lang.com/d/function-units
            TXT, Deprecation::functionUnits);
        }
        $limit_scalar = $limit->assert_int('limit');
        if ($limit_scalar < 1) {
            throw new Sass_Script_Exception("\$limit: Must be greater than 0, was {$limit}.");
        }
        return Sass_Number::create(mt_rand(1, $limit_scalar));
    }
    /**
     * Implements a callable that transforms a number's value
     * using $transform and preserves its units.
     *
     * @param list<Value> $arguments
     * @param callable(float): float $transform
     *
     * @param-immediately-invoked-callable $transform
     */
    private static function number_function(array $arguments, callable $transform): \Scss_Php\Scss_Php\Value\Sass_Number
    {
        $number = $arguments[0]->assert_number('number');
        return Sass_Number::with_units($transform($number->get_value()), $number->get_numerator_units(), $number->get_denominator_units());
    }
}