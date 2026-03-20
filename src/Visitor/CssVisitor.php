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
use Scss_Php\Scss_Php\Ast\Css\Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Css_Supports_Rule;
/**
 * An interface for visitors that traverse CSS statements.
 *
 * @internal
 *
 * @template T
 * @template-extends ModifiableCssVisitor<T>
 */
interface Css_Visitor extends Modifiable_Css_Visitor
{
    /**
     * @return T
     */
    public function visit_css_at_rule(Css_At_Rule $node);
    /**
     * @return T
     */
    public function visit_css_comment(Css_Comment $node);
    /**
     * @return T
     */
    public function visit_css_declaration(Css_Declaration $node);
    /**
     * @return T
     */
    public function visit_css_import(Css_Import $node);
    /**
     * @return T
     */
    public function visit_css_keyframe_block(Css_Keyframe_Block $node);
    /**
     * @return T
     */
    public function visit_css_media_rule(Css_Media_Rule $node);
    /**
     * @return T
     */
    public function visit_css_style_rule(Css_Style_Rule $node);
    /**
     * @return T
     */
    public function visit_css_stylesheet(Css_Stylesheet $node);
    /**
     * @return T
     */
    public function visit_css_supports_rule(Css_Supports_Rule $node);
}