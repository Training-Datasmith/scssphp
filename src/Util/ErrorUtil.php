<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Util;

use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Error_Util
{
    /**
     * @throws \OutOfRangeException
     */
    public static function check_int_in_interval(int $value, int $min_value, int $max_value, ?string $name = null): void
    {
        if ($value < $min_value || $value > $max_value) {
            $name_display = $name ? " {$name}" : '';
            throw new \OutOfRangeException("Invalid value:{$name_display} must be between {$min_value} and {$max_value}: {$value}.");
        }
    }
    public static function format_error_message(string $message, File_Span $span, Trace $sass_trace): string
    {
        $formatted_message = $message . "\n" . $span->highlight();
        foreach (explode("\n", $sass_trace->get_formatted_trace()) as $frame) {
            if ($frame === '') {
                continue;
            }
            $formatted_message .= "\n";
            $formatted_message .= '  ' . $frame;
        }
        return $formatted_message;
    }
    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public static function format_error_message_multiple(string $message, File_Span $span, string $primary_label, array $secondary_spans, Trace $sass_trace): string
    {
        $formatted_message = $message . "\n" . $span->highlight_multiple($primary_label, $secondary_spans);
        foreach (explode("\n", $sass_trace->get_formatted_trace()) as $frame) {
            if ($frame === '') {
                continue;
            }
            $formatted_message .= "\n";
            $formatted_message .= '  ' . $frame;
        }
        return $formatted_message;
    }
}