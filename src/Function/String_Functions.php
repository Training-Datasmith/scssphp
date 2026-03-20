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
namespace Scss_Php\Scss_Php\Function;

use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
/**
 * @internal
 */
final class String_Functions
{
    private static ?int $previous_id = null;
    /**
     * @param list<Value> $arguments
     */
    public static function unquote(array $arguments): Value
    {
        $string = $arguments[0]->assert_string('string');
        if (!$string->has_quotes()) {
            return $string;
        }
        return new Sass_String($string->get_text(), false);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function quote(array $arguments): Value
    {
        $string = $arguments[0]->assert_string('string');
        if ($string->has_quotes()) {
            return $string;
        }
        return new Sass_String($string->get_text(), true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function length(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Number
    {
        $string = $arguments[0]->assert_string('string');
        return Sass_Number::create($string->get_sass_length());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function insert(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $string = $arguments[0]->assert_string('string');
        $insert = $arguments[1]->assert_string('insert');
        $index = $arguments[2]->assert_number('index');
        $index->assert_no_units('index');
        $index_int = $index->assert_int('index');
        // str-insert has unusual behavior for negative inputs. It guarantees that
        // the `$insert` string is at `$index` in the result, which means that we
        // want to insert before `$index` if it's positive and after if it's
        // negative.
        if ($index_int < 0) {
            // +1 because negative indexes start counting from -1 rather than 0, and
            // another +1 because we want to insert *after* that index.
            $index_int = max($string->get_sass_length() + $index_int + 2, 0);
        }
        $codepoint_index = self::codepoint_for_index($index_int, $string->get_sass_length());
        return new Sass_String(mb_substr($string->get_text(), 0, $codepoint_index) . $insert->get_text() . mb_substr($string->get_text(), $codepoint_index), $string->has_quotes());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function index(array $arguments): Value
    {
        $string = $arguments[0]->assert_string('string');
        $substring = $arguments[1]->assert_string('substring');
        $codepoint_index = mb_strpos($string->get_text(), $substring->get_text());
        if ($codepoint_index === false) {
            return Sass_Null::create();
        }
        return Sass_Number::create($codepoint_index + 1);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function slice(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $string = $arguments[0]->assert_string('string');
        $start = $arguments[1]->assert_number('start-at');
        $end = $arguments[2]->assert_number('end-at');
        $start->assert_no_units('start-at');
        $end->assert_no_units('end-at');
        $length_in_codepoints = $string->get_sass_length();
        // No matter what the start index is, an end index of 0 will produce an
        // empty string.
        $end_int = $end->assert_int();
        if ($end_int === 0) {
            return new Sass_String('', $string->has_quotes());
        }
        $start_codepoint = self::codepoint_for_index($start->assert_int(), $length_in_codepoints);
        $end_codepoint = self::codepoint_for_index($end_int, $length_in_codepoints, true);
        if ($end_codepoint === $length_in_codepoints) {
            $end_codepoint--;
        }
        if ($end_codepoint < $start_codepoint) {
            return new Sass_String('', $string->has_quotes());
        }
        return new Sass_String(mb_substr($string->get_text(), $start_codepoint, $end_codepoint + 1 - $start_codepoint), $string->has_quotes());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function to_upper_case(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $string = $arguments[0]->assert_string('string');
        return new Sass_String(String_Util::to_ascii_upper_case($string->get_text()), $string->has_quotes());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function to_lower_case(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $string = $arguments[0]->assert_string('string');
        return new Sass_String(String_Util::to_ascii_lower_case($string->get_text()), $string->has_quotes());
    }
    public static function unique_id(): \Scss_Php\Scss_Php\Value\Sass_String
    {
        if (self::$previous_id === null) {
            self::$previous_id = random_int(0, 36 ** 6);
        }
        // Make it difficult to guess the next ID by randomizing the increase.
        self::$previous_id += random_int(0, 36) + 1;
        if (self::$previous_id > 36 ** 6) {
            self::$previous_id %= 36 ** 6;
        }
        // The leading "u" ensures that the result is a valid identifier.
        return new Sass_String('u' . str_pad(base_convert((string) self::$previous_id, 10, 36), 6, '0', STR_PAD_LEFT), false);
    }
    /**
     * Converts a Sass string index into a codepoint index into a string which
     * has length $lengthInCodepoints measured in codepoints (with `mb_strlen`).
     *
     * A Sass string index is one-based, and uses negative numbers to count
     * backwards from the end of the string.
     *
     * If $index is negative and it points before the beginning of
     * $lengthInCodepoints, this will return `0` if $allowNegative is `false` and
     * the index if it's `true`.
     */
    private static function codepoint_for_index(int $index, int $length_in_codepoints, bool $allow_negative = false): int
    {
        if ($index === 0) {
            return 0;
        }
        if ($index > 0) {
            return min($index - 1, $length_in_codepoints);
        }
        $result = $length_in_codepoints + $index;
        if ($result < 0 && !$allow_negative) {
            return 0;
        }
        return $result;
    }
}