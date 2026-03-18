<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Ast\Css;

use ScssPhp\ScssPhp\Visitor\ModifiableCssVisitor;
use SourceSpan\FileSpan;

/**
 * A modifiable version of {@see CssImport} for use in the evaluation step.
 *
 * @internal
 */
final class ModifiableCssImport extends ModifiableCssNode implements CssImport
{
    private readonly FileSpan $span;

    /**
     * @param CssValue<string> $url
     * @param CssValue<string>|null $modifiers
     */
    public function __construct(/**
     * The URL being imported.
     *
     * This includes quotes.
     */
    private readonly CssValue $url, FileSpan $span, private readonly ?CssValue $modifiers = null)
    {
        $this->span = $span;
    }

    public function getUrl(): CssValue
    {
        return $this->url;
    }

    public function getModifiers(): ?CssValue
    {
        return $this->modifiers;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function accept(ModifiableCssVisitor $visitor)
    {
        return $visitor->visitCssImport($this);
    }
}
