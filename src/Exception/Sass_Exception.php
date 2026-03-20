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

use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Source_Span\File_Span;
interface Sass_Exception extends \Throwable
{
    /**
     * The span associated with this exception.
     */
    public function get_span(): File_Span;
    /**
     * Gets the original message without the location info in it.
     */
    public function get_original_message(): string;
    /**
     * The Sass stack trace at the point this exception was thrown.
     *
     * This includes {@see getSpan}.
     */
    public function get_sass_trace(): Trace;
    /**
     * Converts this to a {@see MultiSpanSassException} with the additional $span and
     * $label.
     *
     * @internal
     */
    public function with_additional_span(File_Span $span, string $label, ?\Throwable $previous = null): Multi_Span_Sass_Exception;
    /**
     * Returns a copy of this as a {@see SassRuntimeException} with $trace as its
     * Sass stack trace.
     *
     * @internal
     */
    public function with_trace(Trace $trace, ?\Throwable $previous = null): Sass_Runtime_Exception;
}