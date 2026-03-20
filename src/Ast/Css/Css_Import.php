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
 * A plain CSS `@import`.
 *
 * @internal
 */
interface Css_Import extends Css_Node
{
    /**
     * The URL being imported.
     *
     * This includes quotes.
     *
     * @return CssValue<string>
     */
    public function get_url(): Css_Value;
    /**
     * The modifiers (such as media or supports queries) attached to this import.
     *
     * @return CssValue<string>|null
     */
    public function get_modifiers(): ?Css_Value;
}