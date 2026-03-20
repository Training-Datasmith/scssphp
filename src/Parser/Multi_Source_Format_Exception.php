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
namespace Scss_Php\Scss_Php\Parser;

use Source_Span\File_Span;
/**
 * @internal
 */
final class Multi_Source_Format_Exception extends Format_Exception
{
    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public function __construct(
        string $message,
        File_Span $span,
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
        parent::__construct($message, $span, $previous);
    }
}