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
 * A modifiable version of {@see CssParentNode} for use in the evaluation step.
 *
 * @internal
 */
abstract class Modifiable_Css_Parent_Node extends Modifiable_Css_Node implements Css_Parent_Node
{
    /**
     * @param list<ModifiableCssNode> $children
     */
    public function __construct(private array $children = [])
    {
    }
    /**
     * @return list<ModifiableCssNode>
     */
    public function get_children(): array
    {
        return $this->children;
    }
    public function is_childless(): bool
    {
        return false;
    }
    /**
     * Returns whether $this is equal to $other, ignoring their child nodes.
     */
    abstract public function equals_ignoring_children(Modifiable_Css_Node $other): bool;
    /**
     * Returns a copy of $this with an empty {@see children} list.
     *
     * This is *not* a deep copy. If other parts of this node are modifiable,
     * they are shared between the new and old nodes.
     */
    abstract public function copy_without_children(): Modifiable_Css_Parent_Node;
    public function add_child(Modifiable_Css_Node $child): void
    {
        $child->set_parent($this, \count($this->children));
        $this->children[] = $child;
    }
    /**
     * @internal
     */
    public function remove_child_at(int $index): void
    {
        array_splice($this->children, $index, 1);
    }
    /**
     * Destructively removes all elements from {@see children}.
     */
    public function clear_children(): void
    {
        foreach ($this->children as $child) {
            $child->reset_parent_references();
        }
        $this->children = [];
    }
}