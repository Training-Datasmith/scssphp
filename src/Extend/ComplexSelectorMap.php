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
namespace Scss_Php\Scss_Php\Extend;

use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
/**
 * @template T
 * @template-extends \SplObjectStorage<ComplexSelector, T>
 *
 * @internal
 */
final class Complex_Selector_Map extends \Spl_Object_Storage
{
    public function get_hash(object $object): string
    {
        \assert($object instanceof Complex_Selector);
        // For ComplexSelector, selectors that are equal by value semantic are exactly the ones that have the same string representation.
        return (string) $object;
    }
    /**
     * @return iterable<T>
     */
    public function get_values(): iterable
    {
        foreach ($this as $selector) {
            yield $this[$selector];
        }
    }
}