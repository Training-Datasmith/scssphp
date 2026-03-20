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

use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Util\Box;
/**
 * An {@see ExtensionStore} that contains no extensions and can have no extensions
 * added.
 *
 * @internal
 */
final class Empty_Extension_Store implements Extension_Store
{
    public function is_empty(): bool
    {
        return true;
    }
    public function get_simple_selectors(): array
    {
        return [];
    }
    public function extensions_where_target(callable $callback): iterable
    {
        return [];
    }
    public function add_selector(Selector_List $selector, ?array $media_context): Box
    {
        throw new \BadMethodCallException("addSelector() can't be called for a const ExtensionStore.");
    }
    public function add_extension(Selector_List $extender, Simple_Selector $target, Extend_Rule $extend, ?array $media_context): void
    {
        throw new \BadMethodCallException("addExtension() can't be called for a const ExtensionStore.");
    }
    public function add_extensions(iterable $extension_stores): void
    {
        throw new \BadMethodCallException("addExtensions() can't be called for a const ExtensionStore.");
    }
    public function clone(): array
    {
        /** @var \SplObjectStorage<SelectorList, Box<SelectorList>> $map */
        $map = new \Spl_Object_Storage();
        return [new Empty_Extension_Store(), $map];
    }
}