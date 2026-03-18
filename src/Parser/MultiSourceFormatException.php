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

namespace ScssPhp\ScssPhp\Parser;

use SourceSpan\FileSpan;

/**
 * @internal
 */
final class MultiSourceFormatException extends FormatException
{
    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public function __construct(string $message, FileSpan $span, /**
     * {@see MultiSpanSassException::$primaryLabel}
     */
    public readonly string $primaryLabel, /**
     * {@see MultiSpanSassException::$secondarySpans}
     */
    public readonly array $secondarySpans, ?\Throwable $previous = null)
    {
        parent::__construct($message, $span, $previous);
    }
}
