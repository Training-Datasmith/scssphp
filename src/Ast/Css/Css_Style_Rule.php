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

use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
/**
* A plain CSS style rule.
*  *
*  * This applies style declarations to elements that match a given selector.
*  * Note that this isn't *strictly* plain CSS, since {@see getSelector} may still
*  * contain placeholder selectors.
*
* @internal
*/
interface Css_Style_Rule extends Css_Parent_Node
{
    /**
     * The selector for this rule.
     */
    public function get_selector(): Selector_List;
    /**
     * The selector for this rule, before any extensions were applied.
     */
    public function get_original_selector(): Selector_List;
    /**
     * Whether this style rule was originally defined in a plain CSS stylesheet.
     *
     * @internal
     */
    public function is_from_plain_css(): bool;
}