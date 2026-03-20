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

use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Comment;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Declaration;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Import;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Keyframe_Block;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Supports_Rule;
/**
 * An interface for visitors that traverse CSS statements.
 *
 * @internal
 *
 * @template T
 */
interface Modifiable_Css_Visitor
{
    /**
     * @return T
     */
    public function visit_css_at_rule(Modifiable_Css_At_Rule $node);
    /**
     * @return T
     */
    public function visit_css_comment(Modifiable_Css_Comment $node);
    /**
     * @return T
     */
    public function visit_css_declaration(Modifiable_Css_Declaration $node);
    /**
     * @return T
     */
    public function visit_css_import(Modifiable_Css_Import $node);
    /**
     * @return T
     */
    public function visit_css_keyframe_block(Modifiable_Css_Keyframe_Block $node);
    /**
     * @return T
     */
    public function visit_css_media_rule(Modifiable_Css_Media_Rule $node);
    /**
     * @return T
     */
    public function visit_css_style_rule(Modifiable_Css_Style_Rule $node);
    /**
     * @return T
     */
    public function visit_css_stylesheet(Modifiable_Css_Stylesheet $node);
    /**
     * @return T
     */
    public function visit_css_supports_rule(Modifiable_Css_Supports_Rule $node);
}