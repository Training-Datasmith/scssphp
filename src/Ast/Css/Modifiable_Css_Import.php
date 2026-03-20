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

use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
use Source_Span\File_Span;
/**
 * A modifiable version of {@see CssImport} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Import extends Modifiable_Css_Node implements Css_Import
{
    private readonly File_Span $span;
    /**
     * @param CssValue<string> $url
     * @param CssValue<string>|null $modifiers
     */
    public function __construct(
        /**
         * The URL being imported.
         *
         * This includes quotes.
         */
        private readonly Css_Value $url,
        File_Span $span,
        private readonly ?Css_Value $modifiers = null
    )
    {
        $this->span = $span;
    }
    public function get_url(): Css_Value
    {
        return $this->url;
    }
    public function get_modifiers(): ?Css_Value
    {
        return $this->modifiers;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_import($this);
    }
}