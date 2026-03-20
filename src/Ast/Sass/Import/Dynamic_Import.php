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

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Import;
use Source_Span\File_Span;
/**
 * An import that will load a Sass file at runtime.
 *
 * @internal
 */
final class Dynamic_Import implements Import
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The URI of the file to import.
         *
         * If this is relative, it's relative to the containing file.
         */
        private readonly string $url_string,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_url(): Uri_Interface
    {
        return Uri::new($this->url_string);
    }
    public function get_url_string(): string
    {
        return $this->url_string;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        return String_Expression::quote_text($this->url_string);
    }
}