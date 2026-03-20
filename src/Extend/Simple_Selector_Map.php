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

use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
/**
 * @template T
 * @template-extends \SplObjectStorage<SimpleSelector, T>
 *
 * @internal
 */
final class Simple_Selector_Map extends \Spl_Object_Storage
{
    public function get_hash(object $object): string
    {
        \assert($object instanceof Simple_Selector);
        // For SimpleSelector, selectors that are equal by value semantic are exactly the ones that have the same string representation.
        return (string) $object;
    }
}