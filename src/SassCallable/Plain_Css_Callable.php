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
namespace Scss_Php\Scss_Php\Sass_Callable;

use Scss_Php\Scss_Php\Util\Equatable;
/**
 * A callable that emits a plain CSS function.
 *
 * This can't be used for mixins.
 *
 * @internal
 */
final class Plain_Css_Callable implements Sass_Callable, Equatable
{
    public function __construct(private readonly string $name)
    {
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function equals(object $other): bool
    {
        return $other instanceof Plain_Css_Callable && $this->name === $other->name;
    }
}