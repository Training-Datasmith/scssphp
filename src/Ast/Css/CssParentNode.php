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
namespace Scss_Php\Scss_Php\Ast\Css;

/**
 * A {@see CssNode} that can have child statements.
 *
 * @internal
 */
interface Css_Parent_Node extends Css_Node
{
    /**
     * The child statements of this node.
     *
     * @return list<CssNode>
     */
    public function get_children(): array;
    /**
     * Whether the rule has no children and should be emitted without curly
     * braces.
     *
     * This implies `children.isEmpty`, but the reverse is not true—for a rule
     * like `@foo {}`, {@see getChildren} is empty but {@see isChildless} is `false`.
     */
    public function is_childless(): bool;
}