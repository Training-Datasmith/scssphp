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
/**
 * @internal
 */
final class Multi_Span_Sass_Runtime_Exception extends Multi_Span_Sass_Exception implements Sass_Runtime_Exception
{
    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public function __construct(string $message, File_Span $span, string $primary_label, array $secondary_spans, private readonly Trace $sass_trace, ?\Throwable $previous = null)
    {
        parent::__construct($message, $span, $primary_label, $secondary_spans, $previous);
    }
    public function get_sass_trace(): Trace
    {
        return $this->sass_trace;
    }
    public function with_additional_span(File_Span $span, string $label, ?\Throwable $previous = null): Multi_Span_Sass_Runtime_Exception
    {
        return new self($this->get_original_message(), $this->get_span(), $this->primary_label, $this->secondary_spans + [$label => $span], $this->sass_trace, $previous);
    }
}