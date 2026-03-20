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
 * A block within a `@keyframes` rule.
 *
 * For example, `10% {opacity: 0.5}`.
 *
 * @internal
 */
interface Css_Keyframe_Block extends Css_Parent_Node
{
    /**
     * The selector for this block.
     *
     * @return CssValue<list<string>>
     */
    public function get_selector(): Css_Value;
}