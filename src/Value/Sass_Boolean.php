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

use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript boolean value.
 */
final class Sass_Boolean extends Value
{
    private static Sass_Boolean $true_instance;
    private static Sass_Boolean $false_instance;
    public static function create(bool $value): Sass_Boolean
    {
        if ($value) {
            return self::$true_instance ??= new self(true);
        }
        return self::$false_instance ??= new self(false);
    }
    private function __construct(private readonly bool $value)
    {
    }
    public function get_value(): bool
    {
        return $this->value;
    }
    public function is_truthy(): bool
    {
        return $this->value;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_boolean($this);
    }
    public function assert_boolean(?string $name = null): Sass_Boolean
    {
        return $this;
    }
    public function unary_not(): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        return self::create(!$this->value);
    }
    public function equals(object $other): bool
    {
        if (!$other instanceof Sass_Boolean) {
            return false;
        }
        return $this->value === $other->value;
    }
}