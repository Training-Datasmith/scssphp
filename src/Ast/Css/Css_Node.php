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

use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Visitor\Css_Visitor;
/**
 * A statement in a plain CSS syntax tree.
 *
 * @internal
 */
interface Css_Node extends Ast_Node
{
    /**
     * The node that contains this, or `null` for the root {@see CssStylesheet} node.
     */
    public function get_parent(): ?Css_Parent_Node;
    /**
     * Whether this was generated from the last node in a nested Sass tree that
     * got flattened during evaluation.
     */
    public function is_group_end(): bool;
    /**
     * Calls the appropriate visit method on $visitor.
     *
     * @template T
     *
     * @param CssVisitor<T> $visitor
     *
     * @return T
     */
    public function accept(Css_Visitor $visitor);
    /**
     * Whether this is invisible and won't be emitted to the compiled stylesheet.
     *
     * Note that this doesn't consider nodes that contain loud comments to be
     * invisible even though they're omitted in compressed mode.
     */
    public function is_invisible(): bool;
    /**
     * Whether this node would be invisible even if style rule selectors within it
     * didn't have bogus combinators.
     *
     * Note that this doesn't consider nodes that contain loud comments to be
     * invisible even though they're omitted in compressed mode.
     */
    public function is_invisible_other_than_bogus_combinators(): bool;
    /**
     * Whether this node will be invisible when loud comments are stripped.
     */
    public function is_invisible_hiding_comments(): bool;
}