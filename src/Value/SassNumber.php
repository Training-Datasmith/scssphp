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

use Jiri_Pudil\Sealed_Classes\Sealed;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript number.
 *
 * Numbers can have units. Although there's no literal syntax for it, numbers
 * support scientific-style numerator and denominator units (for example,
 * `miles/hour`). These are expected to be resolved before being emitted to
 * CSS.
 */
#[Sealed(permits: [Unitless_Sass_Number::class, Single_Unit_Sass_Number::class, Complex_Sass_Number::class])]
abstract class Sass_Number extends Value
{
    final public const PRECISION = 10;
    /**
     * @see https://www.w3.org/TR/css-values-3/
     */
    private const CONVERSIONS = ['in' => ['in' => 1.0, 'pc' => 6.0, 'pt' => 72.0, 'px' => 96.0, 'cm' => 2.54, 'mm' => 25.4, 'q' => 101.6], 'deg' => ['deg' => 360.0, 'grad' => 400.0, 'rad' => 2 * M_PI, 'turn' => 1.0], 's' => ['s' => 1.0, 'ms' => 1000.0], 'Hz' => ['Hz' => 1.0, 'kHz' => 0.001], 'dpi' => ['dpi' => 1.0, 'dpcm' => 1 / 2.54, 'dppx' => 1 / 96]];
    /**
     * A map from human-readable names of unit types to the convertible units that
     * fall into those types.
     */
    private const UNITS_BY_TYPE = ['length' => ['in', 'cm', 'pc', 'mm', 'q', 'pt', 'px'], 'angle' => ['deg', 'grad', 'rad', 'turn'], 'time' => ['s', 'ms'], 'frequency' => ['Hz', 'kHz'], 'pixel density' => ['dpi', 'dpcm', 'dppx']];
    /**
     * A map from units to the human-readable names of those unit types.
     */
    private const TYPES_BY_UNIT = ['in' => 'length', 'cm' => 'length', 'pc' => 'length', 'mm' => 'length', 'q' => 'length', 'pt' => 'length', 'px' => 'length', 'deg' => 'angle', 'grad' => 'angle', 'rad' => 'angle', 'turn' => 'angle', 's' => 'time', 'ms' => 'time', 'Hz' => 'frequency', 'kHz' => 'frequency', 'dpi' => 'pixel density', 'dpcm' => 'pixel density', 'dppx' => 'pixel density'];
    /**
     * @param array{SassNumber, SassNumber}|null $asSlash
     */
    protected function __construct(
        private readonly float $value,
        /**
         * The representation of this number as two slash-separated numbers, if it has one.
         *
         * @internal
         */
        private readonly ?array $as_slash = null
    )
    {
    }
    /**
     * Creates a number, optionally with a single numerator unit.
     *
     * This matches the numbers that can be written as literals.
     * {@see SassNumber::withUnits} can be used to construct more complex units.
     */
    final public static function create(float $value, ?string $unit = null): Sass_Number
    {
        if ($unit === null) {
            return new Unitless_Sass_Number($value);
        }
        return new Single_Unit_Sass_Number($value, $unit);
    }
    /**
     * Creates a number with full $numeratorUnits and $denominatorUnits.
     *
     * @param list<string> $numeratorUnits
     * @param list<string> $denominatorUnits
     */
    final public static function with_units(float $value, array $numerator_units = [], array $denominator_units = []): Sass_Number
    {
        if (empty($numerator_units) && empty($denominator_units)) {
            return new Unitless_Sass_Number($value);
        }
        if (empty($denominator_units) && \count($numerator_units) === 1) {
            return new Single_Unit_Sass_Number($value, $numerator_units[0]);
        }
        if (empty($numerator_units)) {
            return new Complex_Sass_Number($value, $numerator_units, $denominator_units);
        }
        $numerators = $numerator_units;
        $unsimplified_denominators = $denominator_units;
        $denominators = [];
        foreach ($unsimplified_denominators as $denominator) {
            $simplified_away = false;
            foreach ($numerators as $i => $numerator) {
                $factor = self::get_conversion_factor($denominator, $numerator);
                if ($factor === null) {
                    continue;
                }
                $value *= $factor;
                unset($numerators[$i]);
                $simplified_away = true;
                break;
            }
            if (!$simplified_away) {
                $denominators[] = $denominator;
            }
        }
        $numerators = array_values($numerators);
        if (empty($denominators)) {
            if (empty($numerators)) {
                return new Unitless_Sass_Number($value);
            }
            if (\count($numerators) === 1) {
                return new Single_Unit_Sass_Number($value, $numerators[0]);
            }
        }
        return new Complex_Sass_Number($value, $numerators, $denominators);
    }
    /**
     * The value of this number.
     *
     * Note that due to details of floating-point arithmetic, this may be a
     * float even if $this represents an int from Sass's perspective. Use
     * {@see isInt} to determine whether this is an integer, {@see asInt} to get its
     * integer value, or {@see assertInt} to do both at once.
     */
    public function get_value(): float
    {
        return $this->value;
    }
    /**
     * @return list<string>
     */
    abstract public function get_numerator_units(): array;
    /**
     * @return list<string>
     */
    abstract public function get_denominator_units(): array;
    /**
     * @return array{SassNumber, SassNumber}|null
     *
     * @internal
     */
    final public function get_as_slash(): ?array
    {
        return $this->as_slash;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_number($this);
    }
    /**
     * Returns a SassNumber with this value and the same units.
     */
    abstract protected function with_value(float $value): Sass_Number;
    /**
     * @internal
     */
    abstract public function with_slash(Sass_Number $numerator, Sass_Number $denominator): Sass_Number;
    public function without_slash(): Sass_Number
    {
        if ($this->as_slash === null) {
            return $this;
        }
        return $this->with_value($this->value);
    }
    public function assert_number(?string $name = null): Sass_Number
    {
        return $this;
    }
    /**
     * Returns a human-readable string representation of this number's units.
     */
    public function get_unit_string(): string
    {
        return $this->has_units() ? self::build_unit_string($this->get_numerator_units(), $this->get_denominator_units()) : '';
    }
    /**
     * Whether $this is an integer, according to {@see NumberUtil::fuzzyEquals}.
     *
     * The int value can be accessed using {@see asInt} or {@see assertInt}. Note that
     * this may return `false` for very large doubles even though they may be
     * mathematically integers, because not all platforms have a valid
     * representation for integers that large.
     */
    public function is_int(): bool
    {
        return Number_Util::fuzzy_is_int($this->value);
    }
    /**
     * If $this is an integer according to {@see isInt}, returns {@see value} as an int.
     *
     * Otherwise, returns `null`.
     */
    public function as_int(): ?int
    {
        return Number_Util::fuzzy_as_int($this->value);
    }
    /**
     * Returns the value as an int, if it's an integer value according to
     * {@see isInt}.
     *
     * @throws SassScriptException if the value isn't an integer. If this came
     * from a function argument, $name is the argument name (without the `$`).
     * It's used for error reporting.
     */
    public function assert_int(?string $name = null): int
    {
        $integer = Number_Util::fuzzy_as_int($this->value);
        if ($integer !== null) {
            return $integer;
        }
        throw Sass_Script_Exception::for_argument("{$this} is not an int.", $name);
    }
    /**
     * If {@see value} is between $min and $max, returns it.
     *
     * If {@see value} is {@see NumberUtil::fuzzyEquals} to $min or $max, it's clamped to the
     * appropriate value. Otherwise, this throws a {@see SassScriptException}. If this
     * came from a function argument, $name is the argument name (without the
     * `$`). It's used for error reporting.
     *
     * @throws SassScriptException if the value is outside the range
     */
    public function value_in_range(float $min, float $max, ?string $name = null): float
    {
        $result = Number_Util::fuzzy_check_range($this->value, $min, $max);
        if ($result !== null) {
            return $result;
        }
        $unit_string = $this->get_unit_string();
        throw Sass_Script_Exception::for_argument("Expected {$this} to be within {$min}{$unit_string} and {$max}{$unit_string}.", $name);
    }
    /**
     * Like {@see valueInRange}, but with an explicit unit for the expected upper and
     * lower bounds.
     *
     * This exists to solve the confusing error message in https://github.com/sass/dart-sass/issues/1745,
     * and should be removed once https://github.com/sass/sass/issues/3374 fully lands and unitless values
     * are required in these positions.
     *
     * @throws SassScriptException if the value is outside the range
     *
     * @internal
     */
    public function value_in_range_with_unit(float $min, float $max, string $name, string $unit): float
    {
        $result = Number_Util::fuzzy_check_range($this->value, $min, $max);
        if ($result !== null) {
            return $result;
        }
        throw Sass_Script_Exception::for_argument("Expected {$this} to be within {$min}{$unit} and {$max}{$unit}.", $name);
    }
    /**
     * Returns true if the number has units.
     */
    abstract public function has_units(): bool;
    /**
     * Whether $this has more than one numerator unit, or any denominator units.
     *
     * This is `true` for numbers whose units make them unrepresentable as CSS
     * lengths.
     */
    abstract public function has_complex_units(): bool;
    /**
     * Returns whether $this has $unit as its only unit (and as a numerator).
     */
    abstract public function has_unit(string $unit): bool;
    /**
     * Returns whether $this has units that are compatible with $other.
     *
     * Unlike {@see isComparableTo}, unitless numbers are only considered compatible
     * with other unitless numbers.
     */
    public function has_compatible_units(Sass_Number $other): bool
    {
        if (\count($this->get_numerator_units()) !== \count($other->get_numerator_units())) {
            return false;
        }
        if (\count($this->get_denominator_units()) !== \count($other->get_denominator_units())) {
            return false;
        }
        return $this->is_comparable_to($other);
    }
    /**
     * Returns whether $this has units that are possibly-compatible with
     * $other, as defined by the Sass spec.
     *
     * @internal
     */
    abstract public function has_possibly_compatible_units(Sass_Number $other): bool;
    /**
     * Returns whether $this can be coerced to the given unit.
     *
     * This always returns `true` for a unitless number.
     */
    abstract public function compatible_with_unit(string $unit): bool;
    /**
     * Throws a SassScriptException unless $this has $unit as its only unit
     * (and as a numerator).
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_unit(string $unit, ?string $var_name = null): void
    {
        if ($this->has_unit($unit)) {
            return;
        }
        throw Sass_Script_Exception::for_argument(sprintf('Expected %s to have unit "%s".', $this, $unit), $var_name);
    }
    /**
     * Throws a SassScriptException unless $this has no units.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_no_units(?string $var_name = null): void
    {
        if (!$this->has_units()) {
            return;
        }
        throw Sass_Script_Exception::for_argument(sprintf('Expected %s to have no units.', $this), $var_name);
    }
    /**
     * Returns a copy of this number, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * Note that {@see convertValue} is generally more efficient if the value
     * is going to be accessed directly.
     *
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits, or if either number is unitless but the other is not.
     */
    public function convert(array $new_numerator_units, array $new_denominator_units, ?string $name = null): Sass_Number
    {
        return self::with_units($this->convert_value($new_numerator_units, $new_denominator_units, $name), $new_numerator_units, $new_denominator_units);
    }
    /**
     * Returns {@see value}, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits, or if either number is unitless but the other is not.
     */
    public function convert_value(array $new_numerator_units, array $new_denominator_units, ?string $name = null): float
    {
        return $this->convert_or_coerce_value($new_numerator_units, $new_denominator_units, false, $name);
    }
    /**
     * Returns a copy of this number, converted to the same units as $other.
     *
     * Note that {@see convertValueToMatch} is generally more efficient if the value
     * is going to be accessed directly.
     *
     * @param string|null $name      The argument name if this is a function argument
     * @param string|null $otherName The argument name for $other if this is a function argument
     *
     * @throws SassScriptException if the units are not compatible or if either number is unitless but the other is not.
     */
    public function convert_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        return self::with_units($this->convert_value_to_match($other, $name, $other_name), $other->get_numerator_units(), $other->get_denominator_units());
    }
    /**
     * Returns {@see value}, converted to the same units as $other.
     *
     * @param string|null $name      The argument name if this is a function argument
     * @param string|null $otherName The argument name for $other if this is a function argument
     *
     * @throws SassScriptException if the units are not compatible or if either number is unitless but the other is not.
     */
    public function convert_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        return $this->convert_or_coerce_value($other->get_numerator_units(), $other->get_denominator_units(), false, $name, $other, $other_name);
    }
    /**
     * Returns a copy of this number, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * This does not throw an error if this number is unitless and
     * $newNumeratorUnits/$newDenominatorUnits are not empty, or vice versa. Instead,
     * it treats all unitless numbers as convertible to and from all units without
     * changing the value.
     *
     * Note that {@see coerceValue} is generally more efficient if the value
     * is going to be accessed directly.
     *
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits
     */
    public function coerce(array $new_numerator_units, array $new_denominator_units, ?string $name = null): Sass_Number
    {
        return self::with_units($this->coerce_value($new_numerator_units, $new_denominator_units, $name), $new_numerator_units, $new_denominator_units);
    }
    /**
     * Returns {@see value}, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * This does not throw an error if this number is unitless and
     * $newNumeratorUnits/$newDenominatorUnits are not empty, or vice versa. Instead,
     * it treats all unitless numbers as convertible to and from all units without
     * changing the value.
     *
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits
     */
    public function coerce_value(array $new_numerator_units, array $new_denominator_units, ?string $name = null): float
    {
        return $this->convert_or_coerce_value($new_numerator_units, $new_denominator_units, true, $name);
    }
    /**
     * A shorthand for {@see coerceValue} with a single unit
     */
    public function coerce_value_to_unit(string $unit, ?string $name = null): float
    {
        return $this->coerce_value([$unit], [], $name);
    }
    /**
     * Returns a copy of this number, converted to the same units as $other.
     *
     * Unlike {@see convertToMatch}, this does not throw an error if this number is
     * unitless and $other is not, or vice versa. Instead, it treats all unitless
     * numbers as convertible to and from all units without changing the value.
     *
     * Note that {@see coerceValueToMatch} is generally more efficient if the value
     * is going to be accessed directly.
     *
     * @param string|null $name      The argument name if this is a function argument
     * @param string|null $otherName The argument name for $other if this is a function argument
     *
     * @throws SassScriptException if the units are not compatible
     */
    public function coerce_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        return self::with_units($this->coerce_value_to_match($other, $name, $other_name), $other->get_numerator_units(), $other->get_denominator_units());
    }
    /**
     * Returns {@see value}, converted to the same units as $other.
     *
     * Unlike {@see convertValueToMatch}, this does not throw an error if this number
     * is unitless and $other is not, or vice versa. Instead, it treats all unitless
     * numbers as convertible to and from all units without changing the value.
     *
     * @param string|null $name      The argument name if this is a function argument
     * @param string|null $otherName The argument name for $other if this is a function argument
     *
     * @throws SassScriptException if the units are not compatible
     */
    public function coerce_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        return $this->convert_or_coerce_value($other->get_numerator_units(), $other->get_denominator_units(), true, $name, $other, $other_name);
    }
    /**
     * Returns whether this number can be compared to $other.
     *
     * Two numbers can be compared if they have compatible units, or if either
     * number has no units.
     *
     * @internal
     */
    public function is_comparable_to(Sass_Number $other): bool
    {
        if (!$this->has_units() || !$other->has_units()) {
            return true;
        }
        try {
            $this->greater_than($other);
            return true;
        } catch (Sass_Script_Exception) {
            return false;
        }
    }
    public function greater_than(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            // Not using a first-class callable for NumberUtil::fuzzyGreaterThan(),
            // because of a PHP 8.1 bug that results in a segmentation
            // fault, when an Exception is thrown from a function taking the FCC as
            // a parameter.
            //
            // see: https://github.com/php/php-src/commit/b3e26c3036a54e9821ea7119c26cdabe484fe36d
            // see: https://github.com/scssphp/scssphp/issues/752#issuecomment-2423857568
            return Sass_Boolean::create($this->coerce_units($other, Number_Util::fuzzy_greater_than(...)));
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} > {$other}\".");
    }
    public function greater_than_or_equals(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create($this->coerce_units($other, Number_Util::fuzzy_greater_than_or_equals(...)));
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} >= {$other}\".");
    }
    public function less_than(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create($this->coerce_units($other, Number_Util::fuzzy_less_than(...)));
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} < {$other}\".");
    }
    public function less_than_or_equals(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create($this->coerce_units($other, Number_Util::fuzzy_less_than_or_equals(...)));
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} <= {$other}\".");
    }
    public function modulo(Value $other): Sass_Number
    {
        if ($other instanceof Sass_Number) {
            return $this->with_value($this->coerce_units($other, Number_Util::modulo_like_sass(...)));
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} % {$other}\".");
    }
    public function plus(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            return $this->with_value($this->coerce_units($other, fn($num1, $num2): float => $num1 + $num2));
        }
        if (!$other instanceof Sass_Color) {
            return parent::plus($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} + {$other}\".");
    }
    public function minus(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            return $this->with_value($this->coerce_units($other, fn($num1, $num2): float => $num1 - $num2));
        }
        if (!$other instanceof Sass_Color) {
            return parent::minus($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} - {$other}\".");
    }
    public function times(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            if (!$other->has_units()) {
                return $this->with_value($this->value * $other->value);
            }
            return $this->multiply_units($this->value * $other->value, $other->get_numerator_units(), $other->get_denominator_units());
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} * {$other}\".");
    }
    public function divided_by(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            $value = Number_Util::divide_like_sass($this->value, $other->value);
            if (!$other->has_units()) {
                return $this->with_value($value);
            }
            return $this->multiply_units($value, $other->get_denominator_units(), $other->get_numerator_units());
        }
        return parent::divided_by($other);
    }
    public function unary_plus(): Value
    {
        return $this;
    }
    public function equals(object $other): bool
    {
        if (!$other instanceof Sass_Number) {
            return false;
        }
        if (\count($this->get_numerator_units()) !== \count($other->get_numerator_units()) || \count($this->get_denominator_units()) !== \count($other->get_denominator_units())) {
            return false;
        }
        // In Sass, neither NaN nor Infinity are equal to themselves, while PHP defines INF==INF
        if (is_nan($this->value) || is_nan($other->value) || !is_finite($this->value) || !is_finite($other->value)) {
            return false;
        }
        if (!$this->has_units()) {
            return Number_Util::fuzzy_equals($this->value, $other->value);
        }
        if (self::canonicalize_unit_list($this->get_numerator_units()) !== self::canonicalize_unit_list($other->get_numerator_units()) || self::canonicalize_unit_list($this->get_denominator_units()) !== self::canonicalize_unit_list($other->get_denominator_units())) {
            return false;
        }
        return Number_Util::fuzzy_equals($this->value * self::get_canonical_multiplier($this->get_numerator_units()) / self::get_canonical_multiplier($this->get_denominator_units()), $other->value * self::get_canonical_multiplier($other->get_numerator_units()) / self::get_canonical_multiplier($other->get_denominator_units()));
    }
    /**
     * @param list<string> $units
     */
    private static function get_canonical_multiplier(array $units): float
    {
        return array_reduce($units, fn($multiplier, string $unit): float => $multiplier * self::get_canonical_multiplier_for_unit($unit), 1.0);
    }
    private static function get_canonical_multiplier_for_unit(string $unit): float
    {
        foreach (self::CONVERSIONS as $canonical_unit => $conversions) {
            if (isset($conversions[$unit])) {
                \assert(isset($conversions[$canonical_unit]));
                return $conversions[$canonical_unit] / $conversions[$unit];
            }
        }
        return 1.0;
    }
    /**
     * @param list<string> $units
     *
     * @return list<string>
     */
    private static function canonicalize_unit_list(array $units): array
    {
        if (\count($units) === 0) {
            return $units;
        }
        if (\count($units) === 1) {
            if (isset(self::TYPES_BY_UNIT[$units[0]])) {
                $type = self::TYPES_BY_UNIT[$units[0]];
                return [self::UNITS_BY_TYPE[$type][0]];
            }
            return $units;
        }
        $canonical_units = [];
        foreach ($units as $unit) {
            if (isset(self::TYPES_BY_UNIT[$unit])) {
                $type = self::TYPES_BY_UNIT[$unit];
                $canonical_units[] = self::UNITS_BY_TYPE[$type][0];
            } else {
                $canonical_units[] = $unit;
            }
        }
        sort($canonical_units);
        return $canonical_units;
    }
    /**
     * @template T
     *
     * @param callable(float, float): T $operation
     *
     * @return T
     *
     * @param-immediately-invoked-callable $operation
     */
    private function coerce_units(Sass_Number $other, callable $operation): mixed
    {
        try {
            return \call_user_func($operation, $this->value, $other->coerce_value_to_match($this));
        } catch (Sass_Script_Exception $e) {
            // If the conversion fails, re-run it in the other direction. This will
            // generate an error message that prints $this before $other, which is
            // more readable.
            $this->coerce_value_to_match($other);
            throw $e;
            // Should be unreadable as the coercion should throw.
        }
    }
    /**
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     * @param string|null  $name      The argument name if this is a function argument
     * @param string|null  $otherName The argument name for $other if this is a function argument
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits
     */
    private function convert_or_coerce_value(array $new_numerator_units, array $new_denominator_units, bool $coerce_unitless, ?string $name = null, ?Sass_Number $other = null, ?string $other_name = null): float
    {
        assert($other === null || $other->get_numerator_units() === $new_numerator_units && $other->get_denominator_units() === $new_denominator_units, sprintf('Expected %s to have units %s.', $other, self::build_unit_string($new_numerator_units, $new_denominator_units)));
        if ($this->get_numerator_units() === $new_numerator_units && $this->get_denominator_units() === $new_denominator_units) {
            return $this->value;
        }
        $other_has_units = !empty($new_numerator_units) || !empty($new_denominator_units);
        if ($coerce_unitless && (!$other_has_units || !$this->has_units())) {
            return $this->value;
        }
        $value = $this->value;
        $old_numerators = $this->get_numerator_units();
        foreach ($new_numerator_units as $new_numerator) {
            foreach ($old_numerators as $key => $old_numerator) {
                $conversion_factor = self::get_conversion_factor($new_numerator, $old_numerator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value *= $conversion_factor;
                unset($old_numerators[$key]);
                continue 2;
            }
            throw $this->compatibility_exception($other_has_units, $new_numerator_units, $new_denominator_units, $name, $other, $other_name);
        }
        $old_denominators = $this->get_denominator_units();
        foreach ($new_denominator_units as $new_denominator) {
            foreach ($old_denominators as $key => $old_denominator) {
                $conversion_factor = self::get_conversion_factor($new_denominator, $old_denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($old_denominators[$key]);
                continue 2;
            }
            throw $this->compatibility_exception($other_has_units, $new_numerator_units, $new_denominator_units, $name, $other, $other_name);
        }
        if (\count($old_numerators) || \count($old_denominators)) {
            throw $this->compatibility_exception($other_has_units, $new_numerator_units, $new_denominator_units, $name, $other, $other_name);
        }
        return $value;
    }
    /**
     * @param list<string> $newNumeratorUnits
     * @param list<string> $newDenominatorUnits
     */
    private function compatibility_exception(bool $other_has_units, array $new_numerator_units, array $new_denominator_units, ?string $name, ?Sass_Number $other = null, ?string $other_name = null): Sass_Script_Exception
    {
        if ($other !== null) {
            $message = "{$this} and";
            if ($other_name !== null) {
                $message .= " \${$other_name}:";
            }
            $message .= " {$other} have incompatible units";
            if (!$this->has_units() || !$other_has_units) {
                $message .= " (one has units and the other doesn't)";
            }
            return Sass_Script_Exception::for_argument("{$message}.", $name);
        }
        if (!$other_has_units) {
            return Sass_Script_Exception::for_argument("Expected {$this} to have no units.", $name);
        }
        if (\count($new_numerator_units) === 1 && \count($new_denominator_units) === 0 && isset(self::TYPES_BY_UNIT[$new_numerator_units[0]])) {
            $type = self::TYPES_BY_UNIT[$new_numerator_units[0]];
            $article = \in_array($type[0], ['a', 'e', 'i', 'o', 'u'], true) ? 'an' : 'a';
            $supported_units = implode(', ', self::UNITS_BY_TYPE[$type]);
            return Sass_Script_Exception::for_argument("Expected {$this} to have {$article} {$type} unit ({$supported_units}).", $name);
        }
        return Sass_Script_Exception::for_argument(sprintf('Expected %s to have %s %s.', $this, String_Util::pluralize('unit', \count($new_numerator_units) + \count($new_denominator_units)), self::build_unit_string($new_numerator_units, $new_denominator_units)), $name);
    }
    /**
     * @param list<string> $otherNumerators
     * @param list<string> $otherDenominators
     */
    protected function multiply_units(float $value, array $other_numerators, array $other_denominators): Sass_Number
    {
        $new_numerators = [];
        foreach ($this->get_numerator_units() as $numerator) {
            foreach ($other_denominators as $key => $denominator) {
                $conversion_factor = self::get_conversion_factor($numerator, $denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($other_denominators[$key]);
                continue 2;
            }
            $new_numerators[] = $numerator;
        }
        $denominators = $this->get_denominator_units();
        foreach ($other_numerators as $numerator) {
            foreach ($denominators as $key => $denominator) {
                $conversion_factor = self::get_conversion_factor($numerator, $denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($denominators[$key]);
                continue 2;
            }
            $new_numerators[] = $numerator;
        }
        $new_denominators = array_values(array_merge($denominators, $other_denominators));
        return self::with_units($value, $new_numerators, $new_denominators);
    }
    /**
     * Returns the number of [unit1]s per [unit2].
     *
     * Equivalently, `1unit2 * conversionFactor(unit1, unit2) = 1unit1`.
     */
    protected static function get_conversion_factor(string $unit1, string $unit2): ?float
    {
        if ($unit1 === $unit2) {
            return 1;
        }
        foreach (self::CONVERSIONS as $unit_variants) {
            if (isset($unit_variants[$unit1]) && isset($unit_variants[$unit2])) {
                return $unit_variants[$unit1] / $unit_variants[$unit2];
            }
        }
        return null;
    }
    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @param list<string> $numerators
     * @param list<string> $denominators
     */
    private static function build_unit_string(array $numerators, array $denominators): string
    {
        if (!\count($numerators)) {
            if (\count($denominators) === 0) {
                return 'no units';
            }
            if (\count($denominators) === 1) {
                return $denominators[0] . '^-1';
            }
            return '(' . implode('*', $denominators) . ')^-1';
        }
        return implode('*', $numerators) . (\count($denominators) ? '/' . implode('*', $denominators) : '');
    }
    /**
     * Returns a suggested Sass snippet for converting a variable named $name
     * (without `%`) containing this number into a number with the same value and
     * the given $unit.
     *
     * If $unit is null, this forces the number to be unitless.
     *
     * This is used for deprecation warnings when restricting which units are
     * allowed for a given function.
     *
     * @internal
     */
    public function unit_suggestion(string $name, ?string $unit = null): string
    {
        $result = "\${$name}" . implode('', array_map(fn(string $unit): string => " * 1{$unit}", $this->get_denominator_units())) . implode('', array_map(fn(string $unit): string => " / 1{$unit}", $this->get_numerator_units())) . ($unit === null ? '' : " * 1{$unit}");
        return $this->get_numerator_units() === [] ? $result : "calc({$result})";
    }
}