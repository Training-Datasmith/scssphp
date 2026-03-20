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

use Scss_Php\Scss_Php\Sass_Callable\Sass_Callable;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript function reference.
 *
 * A function reference captures a function from the local environment so that
 * it may be passed between modules.
 */
final class Sass_Function extends Value
{
    /**
     * @internal
     */
    public function __construct(private readonly Sass_Callable $callable)
    {
    }
    /**
     * @internal
     */
    public function get_callable(): Sass_Callable
    {
        return $this->callable;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_function($this);
    }
    public function assert_function(?string $name = null): Sass_Function
    {
        return $this;
    }
    public function equals(object $other): bool
    {
        return $other instanceof Sass_Function && Equatable_Util::equals($this->callable, $other->callable);
    }
}