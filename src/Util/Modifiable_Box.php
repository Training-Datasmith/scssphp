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
namespace Scss_Php\Scss_Php\Util;

/**
 * A mutable reference to a (presumably immutable) value.
 *
 * This always uses reference equality, even when the underlying type uses
 * value equality.
 *
 * @template T
 *
 * @internal
 */
final class Modifiable_Box
{
    /**
     * @param T $value
     */
    public function __construct(private mixed $value)
    {
    }
    /**
     * @return T
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * @param T $value
     */
    public function set_value(mixed $value): void
    {
        $this->value = $value;
    }
    /**
     * Returns an unmodifiable reference to this box.
     *
     * The underlying modifiable box may still be modified.
     *
     * @return Box<T>
     */
    public function seal(): Box
    {
        return new Box($this);
    }
}