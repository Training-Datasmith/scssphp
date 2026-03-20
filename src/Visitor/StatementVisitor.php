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

use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Block;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Debug_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Each_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Error_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\For_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Function_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Import_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Include_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Loud_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Media_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Mixin_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Return_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Style_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Supports_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Variable_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Warn_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\While_Rule;
/**
 * An interface for visitors that traverse SassScript statements.
 *
 * @internal
 *
 * @template T
 */
interface Statement_Visitor
{
    /**
     * @return T
     */
    public function visit_at_root_rule(At_Root_Rule $node);
    /**
     * @return T
     */
    public function visit_at_rule(At_Rule $node);
    /**
     * @return T
     */
    public function visit_content_block(Content_Block $node);
    /**
     * @return T
     */
    public function visit_content_rule(Content_Rule $node);
    /**
     * @return T
     */
    public function visit_debug_rule(Debug_Rule $node);
    /**
     * @return T
     */
    public function visit_declaration(Declaration $node);
    /**
     * @return T
     */
    public function visit_each_rule(Each_Rule $node);
    /**
     * @return T
     */
    public function visit_error_rule(Error_Rule $node);
    /**
     * @return T
     */
    public function visit_extend_rule(Extend_Rule $node);
    /**
     * @return T
     */
    public function visit_for_rule(For_Rule $node);
    /**
     * @return T
     */
    public function visit_function_rule(Function_Rule $node);
    /**
     * @return T
     */
    public function visit_if_rule(If_Rule $node);
    /**
     * @return T
     */
    public function visit_import_rule(Import_Rule $node);
    /**
     * @return T
     */
    public function visit_include_rule(Include_Rule $node);
    /**
     * @return T
     */
    public function visit_loud_comment(Loud_Comment $node);
    /**
     * @return T
     */
    public function visit_media_rule(Media_Rule $node);
    /**
     * @return T
     */
    public function visit_mixin_rule(Mixin_Rule $node);
    /**
     * @return T
     */
    public function visit_return_rule(Return_Rule $node);
    /**
     * @return T
     */
    public function visit_silent_comment(Silent_Comment $node);
    /**
     * @return T
     */
    public function visit_style_rule(Style_Rule $node);
    /**
     * @return T
     */
    public function visit_stylesheet(Stylesheet $node);
    /**
     * @return T
     */
    public function visit_supports_rule(Supports_Rule $node);
    /**
     * @return T
     */
    public function visit_variable_declaration(Variable_Declaration $node);
    /**
     * @return T
     */
    public function visit_warn_rule(Warn_Rule $node);
    /**
     * @return T
     */
    public function visit_while_rule(While_Rule $node);
}