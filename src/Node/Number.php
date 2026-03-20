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
namespace Scss_Php\Scss_Php\Node;

use Scss_Php\Scss_Php\Compiler;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Node;
use Scss_Php\Scss_Php\Type;
use Scss_Php\Scss_Php\Util\Number_Util;
/**
 * Dimension + optional units
 *
 * {@internal
 *     This is a work-in-progress.
 *
 *     The \ArrayAccess interface is temporary until the migration is complete.
 * }}
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @template-implements \ArrayAccess<int, mixed>
 */
final class Number extends Node implements \ArrayAccess, \JsonSerializable, \Stringable
{
    public const PRECISION = 10;
    /**
     * @see http://www.w3.org/TR/2012/WD-css3-values-20120308/
     *
     * @phpstan-var array<string, array<string, float|int>>
     */
    private static array $unit_table = ['in' => ['in' => 1, 'pc' => 6, 'pt' => 72, 'px' => 96, 'cm' => 2.54, 'mm' => 25.4, 'q' => 101.6], 'turn' => [
        'deg' => 360,
        'grad' => 400,
        'rad' => 6.283185307179586,
        // 2 * M_PI
        'turn' => 1,
    ], 's' => ['s' => 1, 'ms' => 1000], 'Hz' => ['Hz' => 1, 'kHz' => 0.001], 'dpi' => ['dpi' => 1, 'dpcm' => 1 / 2.54, 'dppx' => 1 / 96]];
    /**
     * @var string[]
     * @phpstan-var list<string>
     */
    private $numerator_units;
    /**
     * @var string[]
     * @phpstan-var list<string>
     */
    private readonly array $denominator_units;
    /**
     * Initialize number
     *
     * @param int|float       $dimension
     * @param string[]|string $numeratorUnits
     * @param string[]        $denominatorUnits
     *
     * @phpstan-param list<string>|string $numeratorUnits
     * @phpstan-param list<string>        $denominatorUnits
     */
    public function __construct(private $dimension, $numerator_units, array $denominator_units = [])
    {
        if (is_string($numerator_units)) {
            $numerator_units = $numerator_units ? [$numerator_units] : [];
        } elseif (isset($numerator_units['numerator_units'], $numerator_units['denominator_units'])) {
            $denominator_units = $numerator_units['denominator_units'];
            $numerator_units = $numerator_units['numerator_units'];
        }
        $this->numerator_units = $numerator_units;
        $this->denominator_units = $denominator_units;
    }
    /**
     * @return float|int
     */
    public function get_dimension()
    {
        return $this->dimension;
    }
    /**
     * @return list<string>
     */
    public function get_numerator_units()
    {
        return $this->numerator_units;
    }
    /**
     * @return list<string>
     */
    public function get_denominator_units()
    {
        return $this->denominator_units;
    }
    /**
     * @return mixed
     */
    #[\Return_Type_Will_Change]
    public function jsonSerialize()
    {
        // Passing a compiler instance makes the method output a Sass representation instead of a CSS one, supporting full units.
        return $this->output(new Compiler());
    }
    /**
     * @return bool
     */
    #[\Return_Type_Will_Change]
    public function offsetExists($offset)
    {
        if ($offset === -3) {
            return !\is_null($this->source_column);
        }
        if ($offset === -2) {
            return !\is_null($this->source_line);
        }
        if ($offset === -1 || $offset === 0 || $offset === 1 || $offset === 2) {
            return true;
        }
        return false;
    }
    /**
     * @return mixed
     */
    #[\Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        switch ($offset) {
            case -3:
                return $this->source_column;
            case -2:
                return $this->source_line;
            case -1:
                return $this->source_index;
            case 0:
                return Type::T_NUMBER;
            case 1:
                return $this->dimension;
            case 2:
                return ['numerator_units' => $this->numerator_units, 'denominator_units' => $this->denominator_units];
        }
    }
    #[\Return_Type_Will_Change]
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Number is immutable');
    }
    #[\Return_Type_Will_Change]
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Number is immutable');
    }
    /**
     * Returns true if the number is unitless
     */
    public function unitless(): bool
    {
        return \count($this->numerator_units) === 0 && \count($this->denominator_units) === 0;
    }
    /**
     * Returns true if the number has any units
     */
    public function has_units(): bool
    {
        return !$this->unitless();
    }
    /**
     * Checks whether the number has exactly this unit
     *
     * @param string $unit
     */
    public function has_unit($unit): bool
    {
        return \count($this->numerator_units) === 1 && \count($this->denominator_units) === 0 && $this->numerator_units[0] === $unit;
    }
    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @return string
     */
    public function unit_str()
    {
        if ($this->unitless()) {
            return '';
        }
        return self::get_unit_string($this->numerator_units, $this->denominator_units);
    }
    /**
     *
     * @throws SassScriptException
     */
    public function value_in_range(float $min, float $max, ?string $name = null): float
    {
        return Number_Util::fuzzy_check_range($this->dimension, $min, $max) ?? throw Sass_Script_Exception::for_argument(sprintf('Expected %s to be within %s%s and %s%3$s.', $this, $min, $this->unit_str(), $max), $name);
    }
    /**
     * @param string    $name
     * @param string    $unit
     *
     * @throws SassScriptException
     *
     * @internal
     */
    public function value_in_range_with_unit(float $min, float $max, ?string $name, $unit): float
    {
        return Number_Util::fuzzy_check_range($this->dimension, $min, $max) ?? throw Sass_Script_Exception::for_argument(sprintf('Expected %s to be within %s%s and %s%3$s.', $this, $min, $unit, $max), $name);
    }
    public function assert_no_units(?string $var_name = null): void
    {
        if ($this->unitless()) {
            return;
        }
        throw Sass_Script_Exception::for_argument(sprintf('Expected %s to have no units.', $this), $var_name);
    }
    /**
     * @param string      $unit
     *
     */
    public function assert_unit($unit, ?string $var_name = null): void
    {
        if ($this->has_unit($unit)) {
            return;
        }
        throw Sass_Script_Exception::for_argument(sprintf('Expected %s to have unit "%s".', $this, $unit), $var_name);
    }
    public function assert_same_unit_or_unitless(Number $other): void
    {
        if ($other->unitless()) {
            return;
        }
        if ($this->numerator_units === $other->numerator_units && $this->denominator_units === $other->denominator_units) {
            return;
        }
        throw new Sass_Script_Exception(sprintf('Incompatible units %s and %s.', self::get_unit_string($this->numerator_units, $this->denominator_units), self::get_unit_string($other->numerator_units, $other->denominator_units)));
    }
    /**
     * Returns a copy of this number, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * This does not throw an error if this number is unitless and
     * $newNumeratorUnits/$newDenominatorUnits are not empty, or vice versa. Instead,
     * it treats all unitless numbers as convertible to and from all units without
     * changing the value.
     *
     * @param string[] $newNumeratorUnits
     * @param string[] $newDenominatorUnits
     *
     *
     * @phpstan-param list<string> $newNumeratorUnits
     * @phpstan-param list<string> $newDenominatorUnits
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits
     */
    public function coerce(array $new_numerator_units, array $new_denominator_units): \Scss_Php\Scss_Php\Node\Number
    {
        return new Number($this->value_in_units($new_numerator_units, $new_denominator_units), $new_numerator_units, $new_denominator_units);
    }
    public function is_comparable_to(Number $other): bool
    {
        if ($this->unitless() || $other->unitless()) {
            return true;
        }
        try {
            $this->greater_than($other);
            return true;
        } catch (Sass_Script_Exception) {
            return false;
        }
    }
    /**
     * @return bool
     */
    public function less_than(Number $other)
    {
        return $this->coerce_units($other, fn($num1, $num2) => $num1 < $num2);
    }
    /**
     * @return bool
     */
    public function less_than_or_equal(Number $other)
    {
        return $this->coerce_units($other, fn($num1, $num2) => $num1 <= $num2);
    }
    /**
     * @return bool
     */
    public function greater_than(Number $other)
    {
        return $this->coerce_units($other, fn($num1, $num2) => $num1 > $num2);
    }
    /**
     * @return bool
     */
    public function greater_than_or_equal(Number $other)
    {
        return $this->coerce_units($other, fn($num1, $num2) => $num1 >= $num2);
    }
    /**
     * @return Number
     */
    public function plus(Number $other)
    {
        return $this->coerce_number($other, fn($num1, $num2) => $num1 + $num2);
    }
    /**
     * @return Number
     */
    public function minus(Number $other)
    {
        return $this->coerce_number($other, fn($num1, $num2) => $num1 - $num2);
    }
    public function unary_minus(): \Scss_Php\Scss_Php\Node\Number
    {
        return new Number(-$this->dimension, $this->numerator_units, $this->denominator_units);
    }
    /**
     * @return Number
     */
    public function modulo(Number $other)
    {
        return $this->coerce_number($other, function ($num1, $num2): int|float {
            if ($num2 == 0) {
                return NAN;
            }
            $result = fmod($num1, $num2);
            if ($result == 0) {
                return 0;
            }
            if ($num2 < 0 xor $num1 < 0) {
                $result += $num2;
            }
            return $result;
        });
    }
    /**
     * @return Number
     */
    public function times(Number $other)
    {
        return $this->multiply_units($this->dimension * $other->dimension, $this->numerator_units, $this->denominator_units, $other->numerator_units, $other->denominator_units);
    }
    /**
     * @return Number
     */
    public function divided_by(Number $other)
    {
        if ($other->dimension == 0) {
            if ($this->dimension == 0) {
                $value = NAN;
            } elseif ($this->dimension > 0) {
                $value = INF;
            } else {
                $value = -INF;
            }
        } else {
            $value = $this->dimension / $other->dimension;
        }
        return $this->multiply_units($value, $this->numerator_units, $this->denominator_units, $other->denominator_units, $other->numerator_units);
    }
    /**
     * @return bool
     */
    public function equals(Number $other)
    {
        // Unitless numbers are convertable to unit numbers, but not equal, so we special-case unitless here.
        if ($this->unitless() !== $other->unitless()) {
            return false;
        }
        // In Sass, neither NaN nor Infinity are equal to themselves, while PHP defines INF==INF
        if (is_nan($this->dimension) || is_nan($other->dimension) || !is_finite($this->dimension) || !is_finite($other->dimension)) {
            return false;
        }
        if ($this->unitless()) {
            return round($this->dimension, self::PRECISION) == round($other->dimension, self::PRECISION);
        }
        try {
            return $this->coerce_units($other, fn($num1, $num2) => round($num1, self::PRECISION) == round($num2, self::PRECISION));
        } catch (Sass_Script_Exception) {
            return false;
        }
    }
    /**
     * Output number
     *
     * @param \ScssPhp\ScssPhp\Compiler $compiler
     */
    public function output(?Compiler $compiler = null): string
    {
        $dimension = round($this->dimension, self::PRECISION);
        if (is_nan($dimension)) {
            return 'NaN';
        }
        if ($dimension === INF) {
            return 'Infinity';
        }
        if ($dimension === -INF) {
            return '-Infinity';
        }
        if ($compiler) {
            $unit = $this->unit_str();
        } elseif (isset($this->numerator_units[0])) {
            $unit = $this->numerator_units[0];
        } else {
            $unit = '';
        }
        $dimension = number_format($dimension, self::PRECISION, '.', '');
        return rtrim(rtrim($dimension, '0'), '.') . $unit;
    }
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->output();
    }
    /**
     * @param callable $operation
     *
     *
     * @phpstan-param callable(int|float, int|float): (int|float) $operation
     */
    private function coerce_number(Number $other, \Closure $operation): \Scss_Php\Scss_Php\Node\Number
    {
        $result = $this->coerce_units($other, $operation);
        if (!$this->unitless()) {
            return new Number($result, $this->numerator_units, $this->denominator_units);
        }
        return new Number($result, $other->numerator_units, $other->denominator_units);
    }
    /**
     * @param callable $operation
     *
     *
     * @phpstan-template T
     * @phpstan-param callable(int|float, int|float): T $operation
     * @phpstan-return T
     */
    private function coerce_units(Number $other, \Closure $operation): mixed
    {
        if (!$this->unitless()) {
            $num1 = $this->dimension;
            $num2 = $other->value_in_units($this->numerator_units, $this->denominator_units);
        } else {
            $num1 = $this->value_in_units($other->numerator_units, $other->denominator_units);
            $num2 = $other->dimension;
        }
        return \call_user_func($operation, $num1, $num2);
    }
    /**
     * @param string[] $numeratorUnits
     * @param string[] $denominatorUnits
     *
     * @return int|float
     *
     * @phpstan-param list<string> $numeratorUnits
     * @phpstan-param list<string> $denominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $numeratorUnits and $denominatorUnits
     */
    private function value_in_units(array $numerator_units, array $denominator_units)
    {
        if ($this->unitless() || \count($numerator_units) === 0 && \count($denominator_units) === 0 || $this->numerator_units === $numerator_units && $this->denominator_units === $denominator_units) {
            return $this->dimension;
        }
        $value = $this->dimension;
        $old_numerators = $this->numerator_units;
        foreach ($numerator_units as $new_numerator) {
            foreach ($old_numerators as $key => $old_numerator) {
                $conversion_factor = self::get_conversion_factor($new_numerator, $old_numerator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value *= $conversion_factor;
                unset($old_numerators[$key]);
                continue 2;
            }
            throw new Sass_Script_Exception(sprintf('Incompatible units %s and %s.', self::get_unit_string($this->numerator_units, $this->denominator_units), self::get_unit_string($numerator_units, $denominator_units)));
        }
        $old_denominators = $this->denominator_units;
        foreach ($denominator_units as $new_denominator) {
            foreach ($old_denominators as $key => $old_denominator) {
                $conversion_factor = self::get_conversion_factor($new_denominator, $old_denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($old_denominators[$key]);
                continue 2;
            }
            throw new Sass_Script_Exception(sprintf('Incompatible units %s and %s.', self::get_unit_string($this->numerator_units, $this->denominator_units), self::get_unit_string($numerator_units, $denominator_units)));
        }
        if (\count($old_numerators) || \count($old_denominators)) {
            throw new Sass_Script_Exception(sprintf('Incompatible units %s and %s.', self::get_unit_string($this->numerator_units, $this->denominator_units), self::get_unit_string($numerator_units, $denominator_units)));
        }
        return $value;
    }
    /**
     * @param string[] $numerators1
     * @param string[] $denominators1
     * @param string[] $numerators2
     * @param string[] $denominators2
     *
     *
     * @phpstan-param list<string> $numerators1
     * @phpstan-param list<string> $denominators1
     * @phpstan-param list<string> $numerators2
     * @phpstan-param list<string> $denominators2
     */
    private function multiply_units(int|float $value, array $numerators1, array $denominators1, array $numerators2, array $denominators2): \Scss_Php\Scss_Php\Node\Number
    {
        $new_numerators = [];
        foreach ($numerators1 as $numerator) {
            foreach ($denominators2 as $key => $denominator) {
                $conversion_factor = self::get_conversion_factor($numerator, $denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($denominators2[$key]);
                continue 2;
            }
            $new_numerators[] = $numerator;
        }
        foreach ($numerators2 as $numerator) {
            foreach ($denominators1 as $key => $denominator) {
                $conversion_factor = self::get_conversion_factor($numerator, $denominator);
                if (\is_null($conversion_factor)) {
                    continue;
                }
                $value /= $conversion_factor;
                unset($denominators1[$key]);
                continue 2;
            }
            $new_numerators[] = $numerator;
        }
        $new_denominators = array_values(array_merge($denominators1, $denominators2));
        return new Number($value, $new_numerators, $new_denominators);
    }
    /**
     * Returns the number of [unit1]s per [unit2].
     *
     * Equivalently, `1unit1 * conversionFactor(unit1, unit2) = 1unit2`.
     *
     * @param string $unit1
     * @param string $unit2
     */
    private static function get_conversion_factor($unit1, $unit2): int|float|null
    {
        if ($unit1 === $unit2) {
            return 1;
        }
        foreach (self::$unit_table as $unit_variants) {
            if (isset($unit_variants[$unit1]) && isset($unit_variants[$unit2])) {
                return $unit_variants[$unit1] / $unit_variants[$unit2];
            }
        }
        return null;
    }
    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @param string[] $numerators
     * @param string[] $denominators
     *
     * @phpstan-param list<string> $numerators
     * @phpstan-param list<string> $denominators
     */
    private static function get_unit_string(array $numerators, array $denominators): string
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
}