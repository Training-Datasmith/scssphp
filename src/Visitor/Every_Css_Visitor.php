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

use Scss_Php\Scss_Php\Ast\Css\Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Comment;
use Scss_Php\Scss_Php\Ast\Css\Css_Declaration;
use Scss_Php\Scss_Php\Ast\Css\Css_Import;
use Scss_Php\Scss_Php\Ast\Css\Css_Keyframe_Block;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Css_Supports_Rule;
use Scss_Php\Scss_Php\Util\Iterable_Util;
/**
 * A visitor that visits each statement in a CSS AST and returns `true` if all
 * of the individual methods return `true`.
 *
 * Each method returns `false` by default.
 *
 * @template-implements CssVisitor<bool>
 * @internal
 */
abstract class Every_Css_Visitor implements Css_Visitor
{
    public function visit_css_at_rule(Css_At_Rule $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
    public function visit_css_comment(Css_Comment $node): bool
    {
        return false;
    }
    public function visit_css_declaration(Css_Declaration $node): bool
    {
        return false;
    }
    public function visit_css_import(Css_Import $node): bool
    {
        return false;
    }
    public function visit_css_keyframe_block(Css_Keyframe_Block $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
    public function visit_css_media_rule(Css_Media_Rule $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
    public function visit_css_style_rule(Css_Style_Rule $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
    public function visit_css_stylesheet(Css_Stylesheet $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
    public function visit_css_supports_rule(Css_Supports_Rule $node): bool
    {
        return Iterable_Util::every($node->get_children(), fn(Css_Node $child) => $child->accept($this));
    }
}