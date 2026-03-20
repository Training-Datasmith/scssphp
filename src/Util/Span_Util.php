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
namespace Scss_Php\Scss_Php\Util;

use Scss_Php\Scss_Php\Parser\String_Scanner;
use Source_Span\File_Span;
use Source_Span\Source_File;
/**
 * @internal
 */
final class Span_Util
{
    public static function bogus_span(): File_Span
    {
        return Source_File::from_string('')->span(0);
    }
    /**
     * Returns this span with all whitespace trimmed from both sides.
     */
    public static function trim(File_Span $span): File_Span
    {
        return self::trim_right(self::trim_left($span));
    }
    /**
     * Returns this span with all leading whitespace trimmed.
     */
    public static function trim_left(File_Span $span): File_Span
    {
        $start = 0;
        $text = $span->get_text();
        $text_length = \strlen($text);
        while ($start < $text_length && Character::is_whitespace($text[$start])) {
            $start++;
        }
        return $span->subspan($start);
    }
    /**
     * Returns this span with all trailing whitespace trimmed.
     */
    public static function trim_right(File_Span $span): File_Span
    {
        $text = $span->get_text();
        $end = \strlen($text) - 1;
        while ($end >= 0 && Character::is_whitespace($text[$end])) {
            $end--;
        }
        return $span->subspan(0, $end + 1);
    }
    /**
     * Returns the span of the identifier at the start of this span.
     *
     * If $includeLeading is greater than 0, that many additional characters
     * will be included from the start of this span before looking for an
     * identifier.
     */
    public static function initial_identifier(File_Span $span, int $include_leading = 0): File_Span
    {
        $scanner = new String_Scanner($span->get_text());
        for ($i = 0; $i < $include_leading; $i++) {
            $scanner->read_utf8char();
        }
        self::scan_identifier($scanner);
        return $span->subspan(0, $scanner->get_position());
    }
    /**
     * Returns a subspan excluding the identifier at the start of this span.
     */
    public static function without_initial_identifier(File_Span $span): File_Span
    {
        $scanner = new String_Scanner($span->get_text());
        self::scan_identifier($scanner);
        return $span->subspan($scanner->get_position());
    }
    /**
     * Returns a subspan excluding a namespace and `.` at the start of this span.
     */
    public static function without_namespace(File_Span $span): File_Span
    {
        return self::without_initial_identifier($span)->subspan(1);
    }
    /**
     * Returns a subspan excluding an initial at-rule and any whitespace after
     * it.
     */
    public static function without_initial_at_rule(File_Span $span): File_Span
    {
        $scanner = new String_Scanner($span->get_text());
        $scanner->expect_char('@');
        self::scan_identifier($scanner);
        return self::trim_left($span->subspan($scanner->get_position()));
    }
    /**
     * Whether $span contains the $target FileSpan.
     *
     * Validates the FileSpans to be in the same file and for the $target to be
     * within $span FileSpan inclusive range [start,end].
     */
    public static function contains(File_Span $span, File_Span $target): bool
    {
        return $span->get_file() === $target->get_file() && $span->get_start()->get_offset() <= $target->get_start()->get_offset() && $span->get_end()->get_offset() >= $target->get_end()->get_offset();
    }
    /**
     * Consumes an identifier from $scanner.
     */
    private static function scan_identifier(String_Scanner $scanner): void
    {
        while (!$scanner->is_done()) {
            $char = $scanner->peek_char();
            if ($char === '\\') {
                Parser_Util::consume_escaped_character($scanner);
            } elseif ($char !== null && Character::is_name($char)) {
                $scanner->read_utf8char();
            } else {
                break;
            }
        }
    }
}