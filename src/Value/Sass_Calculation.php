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
namespace Scss_Php\Scss_Php\Value;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Equatable;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
use Scss_Php\Scss_Php\Warn;
/**
 * A SassScript calculation.
 *
 * Although calculations can in principle have any name or any number of
 * arguments, this class only exposes the specific calculations that are
 * supported by the Sass spec. This ensures that all calculations that the user
 * works with are always fully simplified.
 */
final class Sass_Calculation extends Value
{
    /**
     * Creates a new calculation with the given $name and $arguments
     * that will not be simplified.
     *
     * @param list<object> $arguments
     *
     * @internal
     */
    public static function unsimplified(string $name, array $arguments): Sass_Calculation
    {
        return new Sass_Calculation($name, $arguments);
    }
    /**
     * Creates a `calc()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * @throws SassScriptException
     */
    public static function calc(object $argument): Value
    {
        $argument = self::simplify($argument);
        if ($argument instanceof Sass_Number) {
            return $argument;
        }
        if ($argument instanceof Sass_Calculation) {
            return $argument;
        }
        return new Sass_Calculation('calc', [$argument]);
    }
    /**
     * Creates a `min()` calculation with the given $arguments.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}. It must be passed at
     * least one argument.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * @param list<object> $arguments
     *
     * @throws SassScriptException
     */
    public static function min(array $arguments): Value
    {
        $args = self::simplify_arguments($arguments);
        if (!$args) {
            throw new \InvalidArgumentException('min() must have at least one argument.');
        }
        /** @var SassNumber|null $minimum */
        $minimum = null;
        foreach ($args as $arg) {
            if (!$arg instanceof Sass_Number || $minimum !== null && !$minimum->is_comparable_to($arg)) {
                $minimum = null;
                break;
            }
            if ($minimum === null || $minimum->greater_than($arg)->is_truthy()) {
                $minimum = $arg;
            }
        }
        if ($minimum !== null) {
            return $minimum;
        }
        self::verify_compatible_numbers($args);
        return new Sass_Calculation('min', $args);
    }
    /**
     * Creates a `max()` calculation with the given $arguments.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}. It must be passed at
     * least one argument.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * @param list<object> $arguments
     *
     * @throws SassScriptException
     */
    public static function max(array $arguments): Value
    {
        $args = self::simplify_arguments($arguments);
        if (!$args) {
            throw new \InvalidArgumentException('max() must have at least one argument.');
        }
        /** @var SassNumber|null $maximum */
        $maximum = null;
        foreach ($args as $arg) {
            if (!$arg instanceof Sass_Number || $maximum !== null && !$maximum->is_comparable_to($arg)) {
                $maximum = null;
                break;
            }
            if ($maximum === null || $maximum->less_than($arg)->is_truthy()) {
                $maximum = $arg;
            }
        }
        if ($maximum !== null) {
            return $maximum;
        }
        self::verify_compatible_numbers($args);
        return new Sass_Calculation('max', $args);
    }
    /**
     * Creates a `hypot()` calculation with the given $arguments.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}. It must be passed at
     * least one argument.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * @param list<object> $arguments
     */
    public static function hypot(array $arguments): Value
    {
        $args = self::simplify_arguments($arguments);
        if (!$args) {
            throw new \InvalidArgumentException('hypot() must have at least one argument.');
        }
        self::verify_compatible_numbers($args);
        $sub_total = 0.0;
        $first = $args[0];
        if (!$first instanceof Sass_Number || $first->has_unit('%')) {
            return new Sass_Calculation('hypot', $args);
        }
        foreach ($args as $i => $number) {
            if (!$number instanceof Sass_Number || !$number->has_compatible_units($first)) {
                return new Sass_Calculation('hypot', $args);
            }
            $sass_index = $i + 1;
            $value = $number->convert_value_to_match($first, "number[{$sass_index}]", 'numbers[1]');
            $sub_total += $value * $value;
        }
        return Sass_Number::with_units(sqrt($sub_total), $first->get_numerator_units(), $first->get_denominator_units());
    }
    /**
     * Creates a `sqrt()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function sqrt(object $argument): Value
    {
        return self::single_argument('sqrt', $argument, Number_Util::class . '::sqrt', true);
    }
    /**
     * Creates a `sin()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function sin(object $argument): Value
    {
        return self::single_argument('sin', $argument, Number_Util::class . '::sin');
    }
    /**
     * Creates a `cos()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function cos(object $argument): Value
    {
        return self::single_argument('cos', $argument, Number_Util::class . '::cos');
    }
    /**
     * Creates a `tan()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function tan(object $argument): Value
    {
        return self::single_argument('tan', $argument, Number_Util::class . '::tan');
    }
    /**
     * Creates an `atan()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function atan(object $argument): Value
    {
        return self::single_argument('atan', $argument, Number_Util::class . '::atan', true);
    }
    /**
     * Creates an `asin()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function asin(object $argument): Value
    {
        return self::single_argument('asin', $argument, Number_Util::class . '::asin', true);
    }
    /**
     * Creates an `acos()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function acos(object $argument): Value
    {
        return self::single_argument('acos', $argument, Number_Util::class . '::acos', true);
    }
    /**
     * Creates an `abs()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function abs(object $argument): Value
    {
        $argument = self::simplify($argument);
        if (!$argument instanceof Sass_Number) {
            return new Sass_Calculation('abs', [$argument]);
        }
        if ($argument->has_unit('%')) {
            $message = <<<WARNING
            Passing percentage units to the global abs() function is deprecated.
            In the future, this will emit a CSS abs() function to be resolved by the browser.
            To preserve current behavior: math.abs({$argument})
            
            To emit a CSS abs() now: abs(#{{$argument}})
            More info: https://sass-lang.com/d/abs-percent
            WARNING;
            Warn::for_deprecation($message, Deprecation::absPercent);
        }
        return Number_Util::abs($argument);
    }
    /**
     * Creates an `exp()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function exp(object $argument): Value
    {
        $argument = self::simplify($argument);
        if (!$argument instanceof Sass_Number) {
            return new Sass_Calculation('exp', [$argument]);
        }
        $argument->assert_no_units();
        return Number_Util::pow(Sass_Number::create(M_E), $argument);
    }
    /**
     * Creates a `sign()` calculation with the given $argument.
     *
     * The $argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     */
    public static function sign(object $argument): Value
    {
        $argument = self::simplify($argument);
        if (!$argument instanceof Sass_Number) {
            return new Sass_Calculation('sign', [$argument]);
        }
        if (!$argument->has_units() && (is_nan($argument->get_value()) || $argument->get_value() === 0.0)) {
            return $argument;
        }
        if (!$argument->has_unit('%')) {
            return Sass_Number::create(Number_Util::sign($argument->get_value()))->coerce_to_match($argument);
        }
        return new Sass_Calculation('sign', [$argument]);
    }
    /**
     * Creates a `clamp()` calculation with the given $min, $value, and $max.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than three arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     *
     * @throws SassScriptException
     */
    public static function clamp(object $min, ?object $value = null, ?object $max = null): Value
    {
        if ($value === null && $max !== null) {
            throw new \InvalidArgumentException('If value is null, max must also be null.');
        }
        $min = self::simplify($min);
        if ($value !== null) {
            $value = self::simplify($value);
        }
        if ($max !== null) {
            $max = self::simplify($max);
        }
        if ($min instanceof Sass_Number && $value instanceof Sass_Number && $max instanceof Sass_Number && $min->has_compatible_units($value) && $min->has_compatible_units($max)) {
            if ($value->less_than_or_equals($min)->is_truthy()) {
                return $min;
            }
            if ($value->greater_than_or_equals($max)->is_truthy()) {
                return $max;
            }
            return $value;
        }
        $args = array_values(array_filter([$min, $value, $max]));
        self::verify_compatible_numbers($args);
        self::verify_length($args, 3);
        return new Sass_Calculation('clamp', $args);
    }
    /**
     * Creates a `pow()` calculation with the given $base and $exponent.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than two arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     */
    public static function pow(object $base, ?object $exponent): Value
    {
        $args = [$base];
        if ($exponent !== null) {
            $args[] = $exponent;
        }
        self::verify_length($args, 2);
        $base = self::simplify($base);
        if ($exponent !== null) {
            $exponent = self::simplify($exponent);
        }
        if (!$base instanceof Sass_Number || !$exponent instanceof Sass_Number) {
            return new Sass_Calculation('pow', $args);
        }
        $base->assert_no_units();
        $exponent->assert_no_units();
        return Number_Util::pow($base, $exponent);
    }
    /**
     * Creates a `log()` calculation with the given $number and $base.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * If arguments contains exactly a single argument, the base is set to
     * `math.e` by default.
     */
    public static function log(object $number, ?object $base): Value
    {
        $number = self::simplify($number);
        $args = [$number];
        if ($base !== null) {
            $base = self::simplify($base);
            $args[] = $base;
        }
        if (!$number instanceof Sass_Number || $base !== null && !$base instanceof Sass_Number) {
            return new Sass_Calculation('log', $args);
        }
        $number->assert_no_units();
        if ($base instanceof Sass_Number) {
            $base->assert_no_units();
            return Number_Util::log($number, $base);
        }
        return Number_Util::log($number, null);
    }
    /**
     * Creates a `atan2()` calculation for $y and $x.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than two arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     */
    public static function atan2(object $y, ?object $x): Value
    {
        $y = self::simplify($y);
        $args = [$y];
        if ($x !== null) {
            $x = self::simplify($x);
            $args[] = $x;
        }
        self::verify_length($args, 2);
        self::verify_compatible_numbers($args);
        if (!$y instanceof Sass_Number || !$x instanceof Sass_Number || $y->has_unit('%') || $x->has_unit('%') || !$y->has_compatible_units($x)) {
            return new Sass_Calculation('atan2', $args);
        }
        return Number_Util::atan2($y, $x);
    }
    /**
     * Creates a `rem()` calculation with the given $dividend and $modulus.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than two arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     */
    public static function rem(object $dividend, ?object $modulus): Value
    {
        $dividend = self::simplify($dividend);
        $args = [$dividend];
        if ($modulus !== null) {
            $modulus = self::simplify($modulus);
            $args[] = $modulus;
        }
        self::verify_length($args, 2);
        self::verify_compatible_numbers($args);
        if (!$dividend instanceof Sass_Number || !$modulus instanceof Sass_Number || !$dividend->has_compatible_units($modulus)) {
            return new Sass_Calculation('rem', $args);
        }
        $result = $dividend->modulo($modulus);
        if (Number_Util::sign_including_zero($modulus->get_value()) !== Number_Util::sign_including_zero($dividend->get_value())) {
            if (is_infinite($modulus->get_value())) {
                return $dividend;
            }
            if ($result->get_value() === 0.0) {
                return $result->unary_minus();
            }
            return $result->minus($modulus);
        }
        return $result;
    }
    /**
     * Creates a `mod()` calculation with the given $dividend and $modulus.
     *
     * Each argument must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than two arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     */
    public static function mod(object $dividend, ?object $modulus): Value
    {
        $dividend = self::simplify($dividend);
        $args = [$dividend];
        if ($modulus !== null) {
            $modulus = self::simplify($modulus);
            $args[] = $modulus;
        }
        self::verify_length($args, 2);
        self::verify_compatible_numbers($args);
        if (!$dividend instanceof Sass_Number || !$modulus instanceof Sass_Number || !$dividend->has_compatible_units($modulus)) {
            return new Sass_Calculation('mod', $args);
        }
        return $dividend->modulo($modulus);
    }
    /**
     * Creates a `round()` calculation with the given $strategyOrNumber,
     * $numberOrStep, and $step. Strategy must be either nearest, up, down or
     * to-zero.
     *
     * Number and step must be either a {@see SassNumber}, a {@see SassCalculation}, an
     * unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * This automatically simplifies the calculation, so it may return a
     * {@see SassNumber} rather than a {@see SassCalculation}. It throws an exception if it
     * can determine that the calculation will definitely produce invalid CSS.
     *
     * This may be passed fewer than two arguments, but only if one of the
     * arguments is an unquoted `var()` string.
     */
    public static function round(object $strategy_or_number, ?object $number_or_step = null, ?object $step = null): Value
    {
        $strategy_or_number = self::simplify($strategy_or_number);
        if ($number_or_step !== null) {
            $number_or_step = self::simplify($number_or_step);
        }
        if ($step !== null) {
            $step = self::simplify($step);
        }
        switch (true) {
            case $strategy_or_number instanceof Sass_Number && $number_or_step === null && $step === null:
                return self::match_units(round($strategy_or_number->get_value()), $strategy_or_number);
            case $strategy_or_number instanceof Sass_Number && $number_or_step instanceof Sass_Number && $step === null:
                self::verify_compatible_numbers([$strategy_or_number, $number_or_step]);
                if (!$strategy_or_number->has_compatible_units($number_or_step)) {
                    return new Sass_Calculation('round', [$strategy_or_number, $number_or_step]);
                }
                return self::round_with_step('nearest', $strategy_or_number, $number_or_step);
            case $strategy_or_number instanceof Sass_String && \in_array($strategy_or_number->get_text(), ['nearest', 'up', 'down', 'to-zero'], true) && $number_or_step instanceof Sass_Number && $step instanceof Sass_Number:
                self::verify_compatible_numbers([$number_or_step, $step]);
                if (!$number_or_step->has_compatible_units($step)) {
                    return new Sass_Calculation('round', [$strategy_or_number, $number_or_step, $step]);
                }
                return self::round_with_step($strategy_or_number->get_text(), $number_or_step, $step);
            case $strategy_or_number instanceof Sass_String && \in_array($strategy_or_number->get_text(), ['nearest', 'up', 'down', 'to-zero'], true) && $number_or_step instanceof Sass_String && $step === null:
            case $step === null:
                return new Sass_Calculation('round', [$strategy_or_number, $number_or_step]);
            case $strategy_or_number instanceof Sass_String && \in_array($strategy_or_number->get_text(), ['nearest', 'up', 'down', 'to-zero'], true) && $number_or_step !== null && $step === null:
                throw new Sass_Script_Exception('If strategy is not null, step is required.');
            case $strategy_or_number instanceof Sass_String && \in_array($strategy_or_number->get_text(), ['nearest', 'up', 'down', 'to-zero'], true) && $number_or_step === null && $step === null:
                throw new Sass_Script_Exception('Number to round and step arguments are required.');
            case $strategy_or_number instanceof Sass_String && $number_or_step === null && $step === null:
                return new Sass_Calculation('round', [$strategy_or_number]);
            case $number_or_step === null && $step === null:
                throw new Sass_Script_Exception("Single argument {$strategy_or_number} expected to be simplifiable.");
            case $strategy_or_number instanceof Sass_String && (\in_array($strategy_or_number->get_text(), ['nearest', 'up', 'down', 'to-zero'], true) || $strategy_or_number->is_var()) && $number_or_step !== null:
                return new Sass_Calculation('round', [$strategy_or_number, $number_or_step, $step]);
            case $number_or_step !== null:
                throw new Sass_Script_Exception("{$strategy_or_number} must be either nearest, up, down or to-zero.");
            default:
                throw new Sass_Script_Exception('Invalid parameters.');
        }
    }
    /**
     * Creates and simplifies a {@see CalculationOperation} with the given $operator,
     * $left, and $right.
     *
     * This automatically simplifies the operation, so it may return a
     * {@see SassNumber} rather than a {@see CalculationOperation}.
     *
     * Each of $left and $right must be either a {@see SassNumber}, a
     * {@see SassCalculation}, an unquoted {@see SassString}, or a {@see CalculationOperation}.
     *
     * @throws SassScriptException
     */
    public static function operate(Calculation_Operator $operator, object $left, object $right): object
    {
        return self::operate_internal($operator, $left, $right, false, true);
    }
    /**
     * Like {@see operate}, but with the internal-only $inLegacySassFunction parameter.
     *
     * If $inLegacySassFunction is `true`, this allows unitless numbers to be added and
     * subtracted with numbers with units, for backwards-compatibility with the
     * old global `min()` and `max()` functions.
     *
     * If $simplify is `false`, no simplification will be done.
     *
     * @return SassNumber|CalculationOperation|SassString|SassCalculation|Value
     *
     * @throws SassScriptException
     *
     * @internal
     */
    public static function operate_internal(Calculation_Operator $operator, object $left, object $right, bool $in_legacy_sass_function, bool $simplify): object
    {
        if (!$simplify) {
            return new Calculation_Operation($operator, $left, $right);
        }
        $left = self::simplify($left);
        $right = self::simplify($right);
        if ($operator === Calculation_Operator::PLUS || $operator === Calculation_Operator::MINUS) {
            if ($left instanceof Sass_Number && $right instanceof Sass_Number && ($in_legacy_sass_function ? $left->is_comparable_to($right) : $left->has_compatible_units($right))) {
                return $operator === Calculation_Operator::PLUS ? $left->plus($right) : $left->minus($right);
            }
            self::verify_compatible_numbers([$left, $right]);
            if ($right instanceof Sass_Number && Number_Util::fuzzy_less_than($right->get_value(), 0)) {
                $right = $right->times(Sass_Number::create(-1));
                $operator = $operator === Calculation_Operator::PLUS ? Calculation_Operator::MINUS : Calculation_Operator::PLUS;
            }
            return new Calculation_Operation($operator, $left, $right);
        }
        if ($left instanceof Sass_Number && $right instanceof Sass_Number) {
            return $operator === Calculation_Operator::TIMES ? $left->times($right) : $left->divided_by($right);
        }
        return new Calculation_Operation($operator, $left, $right);
    }
    /**
     * An internal constructor that doesn't perform any validation or
     * simplification.
     *
     * @param list<object> $arguments
     */
    private function __construct(
        /**
         * The calculation's name, such as `"calc"`.
         */
        private readonly string $name,
        /**
         * The calculation's arguments.
         *
         * Each argument is either a {@see SassNumber}, a {@see SassCalculation}, an unquoted
         * {@see SassString}, or a {@see CalculationOperation}.
         */
        private readonly array $arguments
    )
    {
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function is_special_number(): bool
    {
        return true;
    }
    /**
     * @return list<object>
     */
    public function get_arguments(): array
    {
        return $this->arguments;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_calculation($this);
    }
    public function assert_calculation(?string $name = null): Sass_Calculation
    {
        return $this;
    }
    public function plus(Value $other): Value
    {
        if ($other instanceof Sass_String) {
            return parent::plus($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} + {$other}\".");
    }
    public function minus(Value $other): Value
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} - {$other}\".");
    }
    public function unary_plus(): Value
    {
        throw new Sass_Script_Exception("Undefined operation \"+{$this}\".");
    }
    public function unary_minus(): Value
    {
        throw new Sass_Script_Exception("Undefined operation \"-{$this}\".");
    }
    public function equals(object $other): bool
    {
        if (!$other instanceof Sass_Calculation || $this->name !== $other->name) {
            return false;
        }
        if (\count($this->arguments) !== \count($other->arguments)) {
            return false;
        }
        foreach ($this->arguments as $i => $argument) {
            assert($argument instanceof Equatable);
            $other_argument = $other->arguments[$i];
            if (!$argument->equals($other_argument)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns $value coerced to $number's units.
     */
    private static function match_units(float $value, Sass_Number $number): Sass_Number
    {
        return Sass_Number::with_units($value, $number->get_numerator_units(), $number->get_denominator_units());
    }
    /**
     * Returns a rounded $number based on a selected rounding $strategy,
     * to the nearest integer multiple of $step.
     */
    private static function round_with_step(string $strategy, Sass_Number $number, Sass_Number $step): Sass_Number
    {
        if (!\in_array($strategy, ['nearest', 'up', 'down', 'to-zero'], true)) {
            throw new \InvalidArgumentException('$strategy must be either nearest, up, down or to-zero.');
        }
        if (is_infinite($number->get_value()) && is_infinite($step->get_value()) || $step->get_value() === 0.0 || is_nan($number->get_value()) || is_nan($step->get_value())) {
            return self::match_units(NAN, $number);
        }
        if (is_infinite($number->get_value())) {
            return $number;
        }
        if (is_infinite($step->get_value())) {
            if ($number->get_value() === 0.0) {
                return $number;
            }
            switch ($strategy) {
                case 'nearest':
                case 'to-zero':
                    if ($number->get_value() > 0) {
                        return self::match_units(0.0, $number);
                    }
                    return self::match_units(-0.0, $number);
                case 'up':
                    if ($number->get_value() > 0) {
                        return self::match_units(INF, $number);
                    }
                    return self::match_units(-0.0, $number);
                case 'down':
                    if ($number->get_value() < 0) {
                        return self::match_units(-INF, $number);
                    }
                    return self::match_units(0.0, $number);
            }
        }
        $step_with_number_unit = $step->convert_value_to_match($number);
        switch ($strategy) {
            case 'nearest':
                return self::match_units(round($number->get_value() / $step_with_number_unit) * $step_with_number_unit, $number);
            case 'up':
                return self::match_units(($step->get_value() < 0 ? floor($number->get_value() / $step_with_number_unit) : ceil($number->get_value() / $step_with_number_unit)) * $step_with_number_unit, $number);
            case 'down':
                return self::match_units(($step->get_value() < 0 ? ceil($number->get_value() / $step_with_number_unit) : floor($number->get_value() / $step_with_number_unit)) * $step_with_number_unit, $number);
            case 'to-zero':
                if ($number->get_value() < 0) {
                    return self::match_units(ceil($number->get_value() / $step_with_number_unit) * $step_with_number_unit, $number);
                }
                return self::match_units(floor($number->get_value() / $step_with_number_unit) * $step_with_number_unit, $number);
            default:
                return self::match_units(NAN, $number);
        }
    }
    /**
     * @param list<object> $args
     *
     * @return list<object>
     *
     * @throws SassScriptException
     */
    private static function simplify_arguments(array $args): array
    {
        return array_map([self::class, 'simplify'], $args);
    }
    /**
     * @return SassNumber|CalculationOperation|SassString|SassCalculation
     *
     * @throws SassScriptException
     */
    private static function simplify(object $arg): object
    {
        if ($arg instanceof Sass_Number || $arg instanceof Calculation_Operation) {
            return $arg;
        }
        if ($arg instanceof Sass_String) {
            if (!$arg->has_quotes()) {
                return $arg;
            }
            throw new Sass_Script_Exception("Quoted string {$arg} can't be used in a calculation.");
        }
        if ($arg instanceof Sass_Calculation) {
            if ($arg->get_name() === 'calc') {
                $argument = $arg->get_arguments()[0];
                if ($argument instanceof Sass_String && !$argument->has_quotes() && self::needs_parentheses($argument->get_text())) {
                    return new Sass_String("({$argument->get_text()})", false);
                }
                \assert($argument instanceof Sass_Number || $argument instanceof Sass_String || $argument instanceof Sass_Calculation || $argument instanceof Calculation_Operation);
                return $argument;
            }
            return $arg;
        }
        if ($arg instanceof Value) {
            throw new Sass_Script_Exception("Value {$arg} can't be used in a calculation.");
        }
        throw new \InvalidArgumentException(sprintf('Unexpected calculation argument %s.', get_debug_type($arg)));
    }
    /**
     * Returns whether $text needs parentheses if it's the contents of a
     * `calc()` being embedded in another calculation.
     */
    private static function needs_parentheses(string $text): bool
    {
        $first = $text[0];
        if (self::char_needs_parentheses($first)) {
            return true;
        }
        $could_be_var = \strlen($text) > 4 && ($first === 'v' || $first === 'V');
        if (\strlen($text) < 2) {
            return false;
        }
        $second = $text[1];
        if (self::char_needs_parentheses($second)) {
            return true;
        }
        $could_be_var = $could_be_var && ($second === 'a' || $second === 'A');
        if (\strlen($text) < 3) {
            return false;
        }
        $third = $text[2];
        if (self::char_needs_parentheses($third)) {
            return true;
        }
        $could_be_var = $could_be_var && ($third === 'r' || $third === 'R');
        if (\strlen($text) < 4) {
            return false;
        }
        $fourth = $text[3];
        if ($could_be_var && $fourth === '(') {
            return true;
        }
        if (self::char_needs_parentheses($fourth)) {
            return true;
        }
        for ($i = 4; $i < \strlen($text); ++$i) {
            if (self::char_needs_parentheses($text[$i])) {
                return true;
            }
        }
        return false;
    }
    /**
     * Returns whether $character intrinsically needs parentheses if it appears
     * in the unquoted string argument of a `calc()` being embedded in another
     * calculation.
     */
    private static function char_needs_parentheses(string $character): bool
    {
        return $character === '/' || $character === '*' || Character::is_whitespace($character);
    }
    /**
     * Verifies that all the numbers in $args aren't known to be incompatible
     * with one another, and that they don't have units that are too complex for
     * calculations.
     *
     * @param list<object> $args
     *
     * @throws SassScriptException
     */
    private static function verify_compatible_numbers(array $args): void
    {
        foreach ($args as $arg) {
            if (!$arg instanceof Sass_Number) {
                continue;
            }
            if (\count($arg->get_numerator_units()) > 1 || \count($arg->get_denominator_units())) {
                throw new Sass_Script_Exception("Number {$arg} isn't compatible with CSS calculations.");
            }
        }
        for ($i = 0; $i < \count($args); $i++) {
            $number1 = $args[$i];
            if (!$number1 instanceof Sass_Number) {
                continue;
            }
            for ($j = $i + 1; $j < \count($args); $j++) {
                $number2 = $args[$j];
                if (!$number2 instanceof Sass_Number) {
                    continue;
                }
                if ($number1->has_possibly_compatible_units($number2)) {
                    continue;
                }
                throw new Sass_Script_Exception("{$number1} and {$number2} are incompatible.");
            }
        }
    }
    /**
     * Throws a {@see SassScriptException} if $args isn't $expectedLength *and*
     * doesn't contain either a {@see SassString}.
     *
     * @param list<object> $args
     *
     * @throws SassScriptException
     */
    private static function verify_length(array $args, int $expected_length): void
    {
        if (\count($args) === $expected_length) {
            return;
        }
        foreach ($args as $arg) {
            if ($arg instanceof Sass_String) {
                return;
            }
        }
        $length = \count($args);
        $verb = String_Util::pluralize('was', $length, 'were');
        throw new Sass_Script_Exception("{$expected_length} arguments required, but only {$length} {$verb} passed.");
    }
    /**
     * @param callable(SassNumber): SassNumber $mathFunc
     *
     * @param-immediately-invoked-callable $mathFunc
     */
    private static function single_argument(string $name, object $argument, callable $math_func, bool $forbid_units = false): Value
    {
        $argument = self::simplify($argument);
        if (!$argument instanceof Sass_Number) {
            return new Sass_Calculation($name, [$argument]);
        }
        if ($forbid_units) {
            $argument->assert_no_units();
        }
        return $math_func($argument);
    }
}