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
 * A plain CSS `@media` rule.
 *
 * @internal
 */
interface Css_Media_Rule extends Css_Parent_Node
{
    /**
     * The queries for this rule.
     *
     * This is never empty.
     *
     * @return list<CssMediaQuery>
     */
    public function get_queries(): array;
}