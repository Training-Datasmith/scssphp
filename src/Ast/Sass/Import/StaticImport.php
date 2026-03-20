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
namespace Scss_Php\Scss_Php\Ast\Sass\Import;

use Scss_Php\Scss_Php\Ast\Sass\Import;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Source_Span\File_Span;
/**
 * An import that produces a plain CSS `@import` rule.
 *
 * @internal
 */
final class Static_Import implements Import
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The URL for this import.
         *
         * This already contains quotes.
         */
        private readonly Interpolation $url,
        File_Span $span,
        /**
         * The modifiers (such as media or supports queries) attached to this import,
         * or `null` if none are attached.
         */
        private readonly ?Interpolation $modifiers = null
    )
    {
        $this->span = $span;
    }
    public function get_url(): Interpolation
    {
        return $this->url;
    }
    public function get_modifiers(): ?Interpolation
    {
        return $this->modifiers;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        $buffer = (string) $this->url;
        if ($this->modifiers !== null) {
            $buffer .= ' ' . $this->modifiers;
        }
        return $buffer;
    }
}