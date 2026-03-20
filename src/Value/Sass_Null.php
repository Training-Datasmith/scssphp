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
 * The SassScript `null` value.
 */
final class Sass_Null extends Value
{
    private static Sass_Null $instance;
    public static function create(): Sass_Null
    {
        return self::$instance ??= new self();
    }
    private function __construct()
    {
    }
    public function is_truthy(): bool
    {
        return false;
    }
    public function is_blank(): bool
    {
        return true;
    }
    public function real_null(): ?Value
    {
        return null;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_null();
    }
    public function equals(object $other): bool
    {
        return $other instanceof Sass_Null;
    }
    public function unary_not(): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        return Sass_Boolean::create(true);
    }
}