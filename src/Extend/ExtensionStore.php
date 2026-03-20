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

use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Util\Box;
/**
 * Tracks selectors and extensions, and applies the latter to the former.
 *
 * @internal
 */
interface Extension_Store
{
    public function is_empty(): bool;
    /**
     * @return SimpleSelector[]
     */
    public function get_simple_selectors(): array;
    // TODO check the right representation for this
    /**
     * @param callable(SimpleSelector): bool $callback
     * @return iterable<Extension>
     *
     * @param-immediately-invoked-callable $callback
     */
    public function extensions_where_target(callable $callback): iterable;
    /**
     * @param list<CssMediaQuery>|null $mediaContext
     * @return Box<SelectorList>
     */
    public function add_selector(Selector_List $selector, ?array $media_context): Box;
    /**
     * @param list<CssMediaQuery>|null $mediaContext
     */
    public function add_extension(Selector_List $extender, Simple_Selector $target, Extend_Rule $extend, ?array $media_context): void;
    /**
     * @param iterable<ExtensionStore> $extensionStores
     */
    public function add_extensions(iterable $extension_stores): void;
    /**
     * @return array{ExtensionStore, \SplObjectStorage<SelectorList, Box<SelectorList>>}
     */
    public function clone(): array;
}