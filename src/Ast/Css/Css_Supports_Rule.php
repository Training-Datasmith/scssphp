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
 * A plain CSS `@supports` rule.
 *
 * @internal
 */
interface Css_Supports_Rule extends Css_Parent_Node
{
    /**
     * The supports condition.
     *
     * @return CssValue<string>
     */
    public function get_condition(): Css_Value;
}