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

use Scss_Php\Scss_Php\Util\Number_Util;
/**
 * A specialized subclass of {@see SassNumber} for numbers that have exactly one numerator unit.
 *
 * @internal
 */
final class Single_Unit_Sass_Number extends Sass_Number
{
    private const COMPATIBLE_LENGTH_UNITS = ['em', 'rem', 'ex', 'rex', 'cap', 'rcap', 'ch', 'rch', 'ic', 'ric', 'lh', 'rlh', 'vw', 'lvw', 'svw', 'dvw', 'vh', 'lvh', 'svh', 'dvh', 'vi', 'lvi', 'svi', 'dvi', 'vb', 'lvb', 'svb', 'dvb', 'vmin', 'lvmin', 'svmin', 'dvmin', 'vmax', 'lvmax', 'svmax', 'dvmax', 'cqw', 'cqh', 'cqi', 'cqb', 'cqmin', 'cqmax', 'cm', 'mm', 'q', 'in', 'pc', 'pt', 'px'];
    private const KNOWN_COMPATIBILITIES_BY_UNIT = [
        // length
        'em' => self::COMPATIBLE_LENGTH_UNITS,
        'rem' => self::COMPATIBLE_LENGTH_UNITS,
        'ex' => self::COMPATIBLE_LENGTH_UNITS,
        'rex' => self::COMPATIBLE_LENGTH_UNITS,
        'cap' => self::COMPATIBLE_LENGTH_UNITS,
        'rcap' => self::COMPATIBLE_LENGTH_UNITS,
        'ch' => self::COMPATIBLE_LENGTH_UNITS,
        'rch' => self::COMPATIBLE_LENGTH_UNITS,
        'ic' => self::COMPATIBLE_LENGTH_UNITS,
        'ric' => self::COMPATIBLE_LENGTH_UNITS,
        'lh' => self::COMPATIBLE_LENGTH_UNITS,
        'rlh' => self::COMPATIBLE_LENGTH_UNITS,
        'vw' => self::COMPATIBLE_LENGTH_UNITS,
        'lvw' => self::COMPATIBLE_LENGTH_UNITS,
        'svw' => self::COMPATIBLE_LENGTH_UNITS,
        'dvw' => self::COMPATIBLE_LENGTH_UNITS,
        'vh' => self::COMPATIBLE_LENGTH_UNITS,
        'lvh' => self::COMPATIBLE_LENGTH_UNITS,
        'svh' => self::COMPATIBLE_LENGTH_UNITS,
        'dvh' => self::COMPATIBLE_LENGTH_UNITS,
        'vi' => self::COMPATIBLE_LENGTH_UNITS,
        'lvi' => self::COMPATIBLE_LENGTH_UNITS,
        'svi' => self::COMPATIBLE_LENGTH_UNITS,
        'dvi' => self::COMPATIBLE_LENGTH_UNITS,
        'vb' => self::COMPATIBLE_LENGTH_UNITS,
        'lvb' => self::COMPATIBLE_LENGTH_UNITS,
        'svb' => self::COMPATIBLE_LENGTH_UNITS,
        'dvb' => self::COMPATIBLE_LENGTH_UNITS,
        'vmin' => self::COMPATIBLE_LENGTH_UNITS,
        'lvmin' => self::COMPATIBLE_LENGTH_UNITS,
        'svmin' => self::COMPATIBLE_LENGTH_UNITS,
        'dvmin' => self::COMPATIBLE_LENGTH_UNITS,
        'vmax' => self::COMPATIBLE_LENGTH_UNITS,
        'lvmax' => self::COMPATIBLE_LENGTH_UNITS,
        'svmax' => self::COMPATIBLE_LENGTH_UNITS,
        'dvmax' => self::COMPATIBLE_LENGTH_UNITS,
        'cqw' => self::COMPATIBLE_LENGTH_UNITS,
        'cqh' => self::COMPATIBLE_LENGTH_UNITS,
        'cqi' => self::COMPATIBLE_LENGTH_UNITS,
        'cqb' => self::COMPATIBLE_LENGTH_UNITS,
        'cqmin' => self::COMPATIBLE_LENGTH_UNITS,
        'cqmax' => self::COMPATIBLE_LENGTH_UNITS,
        'cm' => self::COMPATIBLE_LENGTH_UNITS,
        'mm' => self::COMPATIBLE_LENGTH_UNITS,
        'q' => self::COMPATIBLE_LENGTH_UNITS,
        'in' => self::COMPATIBLE_LENGTH_UNITS,
        'pc' => self::COMPATIBLE_LENGTH_UNITS,
        'pt' => self::COMPATIBLE_LENGTH_UNITS,
        'px' => self::COMPATIBLE_LENGTH_UNITS,
        // angle
        'deg' => ['deg', 'grad', 'rad', 'turn'],
        'grad' => ['deg', 'grad', 'rad', 'turn'],
        'rad' => ['deg', 'grad', 'rad', 'turn'],
        'turn' => ['deg', 'grad', 'rad', 'turn'],
        // time
        's' => ['s', 'ms'],
        'ms' => ['s', 'ms'],
        // frequency
        'hz' => ['hz', 'khz'],
        'khz' => ['hz', 'khz'],
        // pixel density
        'dpi' => ['dpi', 'dpcm', 'dppx'],
        'dpcm' => ['dpi', 'dpcm', 'dppx'],
        'dppx' => ['dpi', 'dpcm', 'dppx'],
    ];
    /**
     * @param array{SassNumber, SassNumber}|null $asSlash
     */
    public function __construct(float $value, private readonly string $unit, ?array $as_slash = null)
    {
        parent::__construct($value, $as_slash);
    }
    public function get_numerator_units(): array
    {
        return [$this->unit];
    }
    public function get_denominator_units(): array
    {
        return [];
    }
    public function has_units(): bool
    {
        return true;
    }
    public function has_complex_units(): bool
    {
        return false;
    }
    protected function with_value(float $value): \Scss_Php\Scss_Php\Value\Single_Unit_Sass_Number
    {
        return new self($value, $this->unit);
    }
    public function with_slash(Sass_Number $numerator, Sass_Number $denominator): \Scss_Php\Scss_Php\Value\Single_Unit_Sass_Number
    {
        return new self($this->get_value(), $this->unit, [$numerator, $denominator]);
    }
    public function has_unit(string $unit): bool
    {
        return $unit === $this->unit;
    }
    public function has_compatible_units(Sass_Number $other): bool
    {
        return $other instanceof Single_Unit_Sass_Number && $this->compatible_with_unit($other->unit);
    }
    public function has_possibly_compatible_units(Sass_Number $other): bool
    {
        if (!$other instanceof Single_Unit_Sass_Number) {
            return false;
        }
        $known_compatibilities = self::KNOWN_COMPATIBILITIES_BY_UNIT[strtolower($this->unit)] ?? null;
        if ($known_compatibilities === null) {
            return true;
        }
        $other_unit = strtolower($other->unit);
        return !isset(self::KNOWN_COMPATIBILITIES_BY_UNIT[$other_unit]) || \in_array($other_unit, $known_compatibilities, true);
    }
    public function compatible_with_unit(string $unit): bool
    {
        return self::get_conversion_factor($this->unit, $unit) !== null;
    }
    public function coerce_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        if ($other instanceof Single_Unit_Sass_Number) {
            $coerced = $this->try_coerce_to_unit($other->unit);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::coerce_to_match($other, $name, $other_name);
    }
    public function coerce_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        if ($other instanceof Single_Unit_Sass_Number) {
            $coerced = $this->try_coerce_value_to_unit($other->unit);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::coerce_value_to_match($other, $name, $other_name);
    }
    public function convert_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        if ($other instanceof Single_Unit_Sass_Number) {
            $coerced = $this->try_coerce_to_unit($other->unit);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::convert_to_match($other, $name, $other_name);
    }
    public function convert_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        if ($other instanceof Single_Unit_Sass_Number) {
            $coerced = $this->try_coerce_value_to_unit($other->unit);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::convert_value_to_match($other, $name, $other_name);
    }
    public function coerce(array $new_numerator_units, array $new_denominator_units, ?string $name = null): Sass_Number
    {
        if (\count($new_numerator_units) === 1 && \count($new_denominator_units) === 0) {
            $coerced = $this->try_coerce_to_unit($new_numerator_units[0]);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::coerce($new_numerator_units, $new_denominator_units, $name);
    }
    public function coerce_value(array $new_numerator_units, array $new_denominator_units, ?string $name = null): float
    {
        if (\count($new_numerator_units) === 1 && \count($new_denominator_units) === 0) {
            $coerced = $this->try_coerce_value_to_unit($new_numerator_units[0]);
            if ($coerced !== null) {
                return $coerced;
            }
        }
        // Call the parent to generate a consistent error message.
        return parent::coerce_value($new_numerator_units, $new_denominator_units, $name);
    }
    public function coerce_value_to_unit(string $unit, ?string $name = null): float
    {
        $coerced = $this->try_coerce_value_to_unit($unit);
        if ($coerced !== null) {
            return $coerced;
        }
        // Call the parent to generate a consistent error message.
        return parent::coerce_value_to_unit($unit, $name);
    }
    public function unary_minus(): \Scss_Php\Scss_Php\Value\Single_Unit_Sass_Number
    {
        return new self(-$this->get_value(), $this->unit);
    }
    public function equals(object $other): bool
    {
        if ($other instanceof Single_Unit_Sass_Number) {
            $factor = self::get_conversion_factor($other->unit, $this->unit);
            return $factor !== null && Number_Util::fuzzy_equals($this->get_value() * $factor, $other->get_value());
        }
        return false;
    }
    /**
     * @param list<string> $otherNumerators
     * @param list<string> $otherDenominators
     */
    protected function multiply_units(float $value, array $other_numerators, array $other_denominators): Sass_Number
    {
        $new_numerators = $other_numerators;
        $removed = false;
        foreach ($other_denominators as $key => $denominator) {
            $conversion_factor = self::get_conversion_factor($denominator, $this->unit);
            if (\is_null($conversion_factor)) {
                continue;
            }
            $value *= $conversion_factor;
            unset($other_denominators[$key]);
            $removed = true;
            break;
        }
        if (!$removed) {
            array_unshift($new_numerators, $this->unit);
        }
        return Sass_Number::with_units($value, $new_numerators, array_values($other_denominators));
    }
    private function try_coerce_to_unit(string $unit): ?Sass_Number
    {
        if ($unit === $this->unit) {
            return $this;
        }
        $factor = self::get_conversion_factor($unit, $this->unit);
        if ($factor === null) {
            return null;
        }
        return new Single_Unit_Sass_Number($this->get_value() * $factor, $unit);
    }
    private function try_coerce_value_to_unit(string $unit): ?float
    {
        $factor = self::get_conversion_factor($unit, $this->unit);
        if ($factor === null) {
            return null;
        }
        return $this->get_value() * $factor;
    }
}