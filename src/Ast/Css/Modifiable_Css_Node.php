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

use Scss_Php\Scss_Php\Serializer\Serializer;
use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
/**
 * A modifiable version of {@see CssNode}.
 *
 * Almost all CSS nodes are the modifiable classes under the covers. However,
 * modification should only be done within the evaluation step, so the
 * unmodifiable types are used elsewhere to enforce that constraint.
 *
 * @internal
 */
abstract class Modifiable_Css_Node implements Css_Node
{
    private ?Modifiable_Css_Parent_Node $parent = null;
    /**
     * The index of `$this` in parent's children.
     *
     * This makes {@see remove} more efficient.
     */
    private ?int $index_in_parent = null;
    private bool $group_end = false;
    public function get_parent(): ?Modifiable_Css_Parent_Node
    {
        return $this->parent;
    }
    protected function set_parent(Modifiable_Css_Parent_Node $parent, int $index_in_parent): void
    {
        $this->parent = $parent;
        $this->index_in_parent = $index_in_parent;
    }
    public function is_group_end(): bool
    {
        return $this->group_end;
    }
    public function set_group_end(bool $group_end): void
    {
        $this->group_end = $group_end;
    }
    /**
     * Whether this node has a visible sibling after it.
     */
    public function has_following_sibling(): bool
    {
        $parent = $this->parent;
        if ($parent === null) {
            return false;
        }
        assert($this->index_in_parent !== null);
        $siblings = $parent->get_children();
        for ($i = $this->index_in_parent + 1; $i < \count($siblings); $i++) {
            $sibling = $siblings[$i];
            if (!$sibling->is_invisible()) {
                return true;
            }
        }
        return false;
    }
    public function is_invisible(): bool
    {
        return $this->accept(new Is_Invisible_Visitor(true, false));
    }
    public function is_invisible_other_than_bogus_combinators(): bool
    {
        return $this->accept(new Is_Invisible_Visitor(false, false));
    }
    public function is_invisible_hiding_comments(): bool
    {
        return $this->accept(new Is_Invisible_Visitor(true, true));
    }
    /**
     * Calls the appropriate visit method on $visitor.
     *
     * @template T
     *
     * @param ModifiableCssVisitor<T> $visitor
     *
     * @return T
     */
    abstract public function accept(Modifiable_Css_Visitor $visitor);
    /**
     * Removes $this from {@see parent}'s child list.
     *
     * @throws \LogicException if {@see parent} is `null`.
     */
    public function remove(): void
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new \LogicException("Can't remove a node without a parent.");
        }
        assert($this->index_in_parent !== null);
        $parent->remove_child_at($this->index_in_parent);
        $children = $parent->get_children();
        for ($i = $this->index_in_parent; $i < \count($children); $i++) {
            $child = $children[$i];
            assert($child->index_in_parent !== null);
            $child->index_in_parent = $child->index_in_parent - 1;
        }
        $this->parent = null;
        $this->index_in_parent = null;
    }
    /**
     * @internal
     */
    protected function reset_parent_references(): void
    {
        $this->parent = null;
        $this->index_in_parent = null;
    }
    public function __toString(): string
    {
        return Serializer::serialize($this, true)->css;
    }
}