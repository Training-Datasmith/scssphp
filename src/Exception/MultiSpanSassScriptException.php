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
final class Multi_Span_Sass_Script_Exception extends Sass_Script_Exception
{
    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public function __construct(
        string $message,
        /**
         * {@see MultiSpanSassException::$primaryLabel}
         */
        public readonly string $primary_label,
        /**
         * {@see MultiSpanSassException::$secondarySpans}
         */
        public readonly array $secondary_spans,
        ?\Throwable $previous = null
    )
    {
        parent::__construct($message, 0, $previous);
    }
    public function with_span(File_Span $span): Multi_Span_Sass_Exception
    {
        return new Multi_Span_Sass_Exception($this->message, $span, $this->primary_label, $this->secondary_spans, $this);
    }
}