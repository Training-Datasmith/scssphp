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
namespace Scss_Php\Scss_Php\Value;

use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript string.
 *
 * Strings can either be quoted or unquoted. Unquoted strings are usually CSS
 * identifiers, but they may contain any text.
 */
final class Sass_String extends Value
{
    public function __construct(
        /**
         * The contents of the string.
         *
         * For quoted strings, this is the semantic content—any escape sequences that
         * were been written in the source text are resolved to their Unicode values.
         * For unquoted strings, though, escape sequences are preserved as literal
         * backslashes.
         *
         * This difference allows us to distinguish between identifiers with escapes,
         * such as `url\u28 http://example.com\u29`, and unquoted strings that
         * contain characters that aren't valid in identifiers, such as
         * `url(http://example.com)`. Unfortunately, it also means that we don't
         * consider `foo` and `f\6F\6F` the same string.
         */
        private readonly string $text,
        /**
         * Whether this string has quotes.
         */
        private readonly bool $quotes = true
    )
    {
    }
    public function get_text(): string
    {
        return $this->text;
    }
    public function has_quotes(): bool
    {
        return $this->quotes;
    }
    public function get_sass_length(): int
    {
        return mb_strlen($this->text, 'UTF-8');
    }
    public function is_special_number(): bool
    {
        if ($this->quotes) {
            return false;
        }
        if (\strlen($this->text) < \strlen('min(_)')) {
            return false;
        }
        $first = $this->text[0];
        if ($first === 'c' || $first === 'C') {
            $second = $this->text[1];
            if ($second === 'l' || $second === 'L') {
                return ($this->text[2] === 'a' || $this->text[2] === 'A') && ($this->text[3] === 'm' || $this->text[3] === 'M') && ($this->text[4] === 'p' || $this->text[4] === 'P') && $this->text[5] === '(';
            }
            if ($second === 'a' || $second === 'A') {
                return ($this->text[2] === 'l' || $this->text[2] === 'L') && ($this->text[3] === 'c' || $this->text[3] === 'C') && $this->text[4] === '(';
            }
            return false;
        }
        if ($first === 'v' || $first === 'V') {
            return ($this->text[1] === 'a' || $this->text[1] === 'A') && ($this->text[2] === 'r' || $this->text[2] === 'R') && $this->text[3] === '(';
        }
        if ($first === 'e' || $first === 'E') {
            return ($this->text[1] === 'n' || $this->text[1] === 'N') && ($this->text[2] === 'v' || $this->text[2] === 'V') && $this->text[3] === '(';
        }
        if ($first === 'm' || $first === 'M') {
            $second = $this->text[1];
            if ($second === 'a' || $second === 'A') {
                return ($this->text[2] === 'x' || $this->text[2] === 'X') && $this->text[3] === '(';
            }
            if ($second === 'i' || $second === 'I') {
                return ($this->text[2] === 'n' || $this->text[2] === 'N') && $this->text[3] === '(';
            }
            return false;
        }
        return false;
    }
    public function is_var(): bool
    {
        if ($this->quotes) {
            return false;
        }
        if (\strlen($this->text) < \strlen('var(--_)')) {
            return false;
        }
        return ($this->text[0] === 'v' || $this->text[0] === 'V') && ($this->text[1] === 'a' || $this->text[1] === 'A') && ($this->text[2] === 'r' || $this->text[2] === 'R') && $this->text[3] === '(';
    }
    public function is_blank(): bool
    {
        return !$this->quotes && $this->text === '';
    }
    /**
     * Converts $sassIndex into a PHP-style index into {@see text}.
     *
     * Sass indexes are one-based, while PHP indexes are zero-based. Sass
     * indexes may also be negative in order to index from the end of the string.
     *
     * In addition, Sass indices refer to Unicode code points while PHP string
     * indices refer to bytes. For example, the character U+1F60A,
     * Smiling Face With Smiling Eyes, is a single Unicode code point but is
     * represented in UTF-8 as several bytes (`0xF0`, `0x9F`, `0x98` and `0x8A`). So in
     * PHP, `substr("a😊b", 1, 1)` returns `"\xF0"`, whereas in Sass
     * `str-slice("a😊b", 1, 1)` returns `"😊"`.
     *
     * @throws SassScriptException if $sassIndex isn't a number, if that
     * number isn't an integer, or if that integer isn't a valid index for this
     * string. If $sassIndex came from a function argument, $name is the
     * argument name (without the `$`). It's used for error reporting.
     */
    public function sass_index_to_string_index(Value $sass_index, ?string $name = null): int
    {
        $codepoint_index = $this->sass_index_to_code_point_index($sass_index, $name);
        if ($codepoint_index === 0) {
            return 0;
        }
        return \strlen(mb_substr($this->text, 0, $codepoint_index, 'UTF-8'));
    }
    /**
     * Converts $sassIndex into a PHP-style index into codepoints.
     *
     * This index is suitable to use with functions dealing with codepoints
     * (i.e. the mbstring functions).
     *
     * Sass indexes are one-based, while PHP indexes are zero-based. Sass
     * indexes may also be negative in order to index from the end of the string.
     *
     * See also {@see sassIndexToStringIndex}, which is an index into {@see getText} directly.
     *
     * @throws SassScriptException if $sassIndex isn't a number, if that
     * number isn't an integer, or if that integer isn't a valid index for this
     * string. If $sassIndex came from a function argument, $name is the
     * argument name (without the `$`). It's used for error reporting.
     */
    public function sass_index_to_code_point_index(Value $sass_index, ?string $name = null): int
    {
        $index = $sass_index->assert_number($name)->assert_int($name);
        if ($index === 0) {
            throw Sass_Script_Exception::for_argument('String index may not be 0.', $name);
        }
        $sass_length = $this->get_sass_length();
        if (abs($index) > $sass_length) {
            throw Sass_Script_Exception::for_argument("Invalid index {$sass_index} for a string with {$sass_length} characters.", $name);
        }
        return $index < 0 ? $sass_length + $index : $index - 1;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_string($this);
    }
    public function assert_string(?string $name = null): Sass_String
    {
        return $this;
    }
    public function plus(Value $other): \Scss_Php\Scss_Php\Value\Sass_String
    {
        if ($other instanceof Sass_String) {
            return new Sass_String($this->text . $other->get_text(), $this->quotes);
        }
        return new Sass_String($this->text . $other->to_css_string(), $this->quotes);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Sass_String && $this->text === $other->text;
    }
}