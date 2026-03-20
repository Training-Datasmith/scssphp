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
 * An unknown plain CSS at-rule.
 *
 * @internal
 */
interface Css_At_Rule extends Css_Parent_Node
{
    /**
     * The name of this rule.
     *
     * @return CssValue<string>
     */
    public function get_name(): Css_Value;
    /**
     * The value of this rule.
     *
     * @return CssValue<string>|null
     */
    public function get_value(): ?Css_Value;
}