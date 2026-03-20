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
namespace Scss_Php\Scss_Php\Exception;

use Source_Span\File_Span;
/**
 * @internal
 */
interface Sass_Runtime_Exception extends Sass_Exception
{
    public function with_additional_span(File_Span $span, string $label, ?\Throwable $previous = null): Multi_Span_Sass_Runtime_Exception;
}