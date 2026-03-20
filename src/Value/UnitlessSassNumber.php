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
 * A specialized subclass of {@see SassNumber} for numbers that have no units.
 *
 * @internal
 */
final class Unitless_Sass_Number extends Sass_Number
{
    public function get_numerator_units(): array
    {
        return [];
    }
    public function get_denominator_units(): array
    {
        return [];
    }
    public function has_units(): bool
    {
        return false;
    }
    public function has_complex_units(): bool
    {
        return false;
    }
    protected function with_value(float $value): \Scss_Php\Scss_Php\Value\Unitless_Sass_Number
    {
        return new self($value);
    }
    public function with_slash(Sass_Number $numerator, Sass_Number $denominator): \Scss_Php\Scss_Php\Value\Unitless_Sass_Number
    {
        return new self($this->get_value(), [$numerator, $denominator]);
    }
    public function has_unit(string $unit): bool
    {
        return false;
    }
    public function has_compatible_units(Sass_Number $other): bool
    {
        return $other instanceof Unitless_Sass_Number;
    }
    public function has_possibly_compatible_units(Sass_Number $other): bool
    {
        return $other instanceof Unitless_Sass_Number;
    }
    public function compatible_with_unit(string $unit): bool
    {
        return true;
    }
    public function coerce_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        return $other->with_value($this->get_value());
    }
    public function coerce_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        return $this->get_value();
    }
    public function convert_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): Sass_Number
    {
        if (!$other->has_units()) {
            return $this;
        }
        // Call the parent to generate a consistent error message.
        return parent::convert_to_match($other, $name, $other_name);
    }
    public function convert_value_to_match(Sass_Number $other, ?string $name = null, ?string $other_name = null): float
    {
        if (!$other->has_units()) {
            return $this->get_value();
        }
        // Call the parent to generate a consistent error message.
        return parent::convert_value_to_match($other, $name, $other_name);
    }
    public function coerce(array $new_numerator_units, array $new_denominator_units, ?string $name = null): Sass_Number
    {
        return Sass_Number::with_units($this->get_value(), $new_numerator_units, $new_denominator_units);
    }
    public function coerce_value(array $new_numerator_units, array $new_denominator_units, ?string $name = null): float
    {
        return $this->get_value();
    }
    public function coerce_value_to_unit(string $unit, ?string $name = null): float
    {
        return $this->get_value();
    }
    public function greater_than(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create(Number_Util::fuzzy_greater_than($this->get_value(), $other->get_value()));
        }
        return parent::greater_than($other);
    }
    public function greater_than_or_equals(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create(Number_Util::fuzzy_greater_than_or_equals($this->get_value(), $other->get_value()));
        }
        return parent::greater_than_or_equals($other);
    }
    public function less_than(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create(Number_Util::fuzzy_less_than($this->get_value(), $other->get_value()));
        }
        return parent::less_than($other);
    }
    public function less_than_or_equals(Value $other): Sass_Boolean
    {
        if ($other instanceof Sass_Number) {
            return Sass_Boolean::create(Number_Util::fuzzy_less_than_or_equals($this->get_value(), $other->get_value()));
        }
        return parent::less_than_or_equals($other);
    }
    public function modulo(Value $other): Sass_Number
    {
        if ($other instanceof Sass_Number) {
            return $other->with_value(Number_Util::modulo_like_sass($this->get_value(), $other->get_value()));
        }
        return parent::modulo($other);
    }
    public function plus(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            return $other->with_value($this->get_value() + $other->get_value());
        }
        return parent::plus($other);
    }
    public function minus(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            return $other->with_value($this->get_value() - $other->get_value());
        }
        return parent::minus($other);
    }
    public function times(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            return $other->with_value($this->get_value() * $other->get_value());
        }
        return parent::times($other);
    }
    public function divided_by(Value $other): Value
    {
        if ($other instanceof Sass_Number) {
            $value = Number_Util::divide_like_sass($this->get_value(), $other->get_value());
            if ($other->has_units()) {
                return Sass_Number::with_units($value, $other->get_denominator_units(), $other->get_numerator_units());
            }
            return new self($value);
        }
        return parent::divided_by($other);
    }
    public function unary_minus(): \Scss_Php\Scss_Php\Value\Unitless_Sass_Number
    {
        return new self(-$this->get_value());
    }
    public function equals(object $other): bool
    {
        return $other instanceof Unitless_Sass_Number && Number_Util::fuzzy_equals($this->get_value(), $other->get_value());
    }
}