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
namespace Scss_Php\Scss_Php\Visitor;

use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Callable_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Block;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Debug_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Each_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Error_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\For_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Function_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Clause;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Import_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Include_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Loud_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Media_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Mixin_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Parent_Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Return_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Style_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Supports_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Variable_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Warn_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\While_Rule;
use Scss_Php\Scss_Php\Util\Iterable_Util;
/**
 * A StatementVisitor whose `visit*` methods default to returning `null`, but
 * which returns the first non-`null` value returned by any method.
 *
 * This can be extended to find the first instance of particular nodes in the
 * AST.
 *
 * @internal
 *
 * @template T
 * @template-implements StatementVisitor<T|null>
 */
abstract class Statement_Search_Visitor implements Statement_Visitor
{
    public function visit_at_root_rule(At_Root_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_at_rule(At_Rule $node)
    {
        if ($node->get_children() !== null) {
            return $this->visit_children($node->get_children());
        }
        return null;
    }
    public function visit_content_block(Content_Block $node)
    {
        return $this->visit_callable_declaration($node);
    }
    public function visit_content_rule(Content_Rule $node)
    {
        return null;
    }
    public function visit_debug_rule(Debug_Rule $node)
    {
        return null;
    }
    public function visit_declaration(Declaration $node)
    {
        if ($node->get_children() !== null) {
            return $this->visit_children($node->get_children());
        }
        return null;
    }
    public function visit_each_rule(Each_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_error_rule(Error_Rule $node)
    {
        return null;
    }
    public function visit_extend_rule(Extend_Rule $node)
    {
        return null;
    }
    public function visit_for_rule(For_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_function_rule(Function_Rule $node)
    {
        return $this->visit_callable_declaration($node);
    }
    public function visit_if_rule(If_Rule $node)
    {
        $value = Iterable_Util::search($node->get_clauses(), fn(If_Clause $clause) => Iterable_Util::search($clause->get_children(), fn(Statement $child) => $child->accept($this)));
        if ($node->get_last_clause() !== null) {
            $value ??= Iterable_Util::search($node->get_last_clause()->get_children(), fn(Statement $child) => $child->accept($this));
        }
        return $value;
    }
    public function visit_import_rule(Import_Rule $node)
    {
        return null;
    }
    public function visit_include_rule(Include_Rule $node)
    {
        if ($node->get_content() !== null) {
            return $this->visit_content_block($node->get_content());
        }
        return null;
    }
    public function visit_loud_comment(Loud_Comment $node)
    {
        return null;
    }
    public function visit_media_rule(Media_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_mixin_rule(Mixin_Rule $node)
    {
        return $this->visit_callable_declaration($node);
    }
    public function visit_return_rule(Return_Rule $node)
    {
        return null;
    }
    public function visit_silent_comment(Silent_Comment $node)
    {
        return null;
    }
    public function visit_style_rule(Style_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_stylesheet(Stylesheet $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_supports_rule(Supports_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    public function visit_variable_declaration(Variable_Declaration $node)
    {
        return null;
    }
    public function visit_warn_rule(Warn_Rule $node)
    {
        return null;
    }
    public function visit_while_rule(While_Rule $node)
    {
        return $this->visit_children($node->get_children());
    }
    /**
     * Visits each of $node's expressions and children.
     *
     * The default implementations of {@see visitFunctionRule} and {@see visitMixinRule}
     * call this.
     *
     * @return T|null
     */
    protected function visit_callable_declaration(Callable_Declaration $node)
    {
        return $this->visit_children($node->get_children());
    }
    /**
     * Visits each child in $children.
     *
     * The default implementation of the visit methods for all {@see ParentStatement}s
     * call this.
     *
     * @param Statement[] $children
     *
     * @return T|null
     */
    protected function visit_children(array $children)
    {
        return Iterable_Util::search($children, fn(Statement $child) => $child->accept($this));
    }
}