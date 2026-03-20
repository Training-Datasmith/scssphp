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

/**
 * A specialized subclass of {@see SassNumber} for numbers that are neither {@see UnitlessSassNumber} nor {@see SingleUnitSassNumber}.
 *
 * @internal
 */
final class Complex_Sass_Number extends Sass_Number
{
    /**
     * @var list<string>
     */
    private readonly array $numerator_units;
    /**
     * @var list<string>
     */
    private readonly array $denominator_units;
    /**
     * @param list<string>                       $numeratorUnits
     * @param list<string>                       $denominatorUnits
     * @param array{SassNumber, SassNumber}|null $asSlash
     */
    public function __construct(float $value, array $numerator_units, array $denominator_units, ?array $as_slash = null)
    {
        assert(\count($numerator_units) > 1 || \count($denominator_units) > 0);
        parent::__construct($value, $as_slash);
        $this->numerator_units = $numerator_units;
        $this->denominator_units = $denominator_units;
    }
    public function get_numerator_units(): array
    {
        return $this->numerator_units;
    }
    public function get_denominator_units(): array
    {
        return $this->denominator_units;
    }
    public function has_units(): bool
    {
        return true;
    }
    public function has_complex_units(): bool
    {
        return true;
    }
    public function has_unit(string $unit): bool
    {
        return false;
    }
    public function compatible_with_unit(string $unit): bool
    {
        return false;
    }
    public function has_possibly_compatible_units(Sass_Number $other): bool
    {
        // This logic is well-defined, and we could implement it in principle.
        // However, it would be fairly complex and there's no clear need for it yet.
        throw new \BadMethodCallException(__METHOD__ . 'is not implemented.');
    }
    protected function with_value(float $value): \Scss_Php\Scss_Php\Value\Complex_Sass_Number
    {
        return new self($value, $this->numerator_units, $this->denominator_units);
    }
    public function with_slash(Sass_Number $numerator, Sass_Number $denominator): \Scss_Php\Scss_Php\Value\Complex_Sass_Number
    {
        return new self($this->get_value(), $this->numerator_units, $this->denominator_units, [$numerator, $denominator]);
    }
}