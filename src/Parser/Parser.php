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

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Exception\Multi_Span_Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Logger\Quiet_Logger;
use Scss_Php\Scss_Php\Source_Span\Lazy_File_Span;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Parser_Util;
use Source_Span\File_Location;
use Source_Span\File_Span;
/**
 * @internal
 */
class Parser
{
    protected readonly String_Scanner $scanner;
    /**
     * Parses $text as a CSS identifier and returns the result.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse_identifier(string $text, ?Logger_Interface $logger = null): string
    {
        return (new Parser($text, $logger))->do_parse_identifier();
    }
    /**
     * Returns whether $text is a valid CSS identifier.
     */
    public static function is_identifier(string $text, ?Logger_Interface $logger = null): bool
    {
        try {
            self::parse_identifier($text, $logger);
            return true;
        } catch (Sass_Format_Exception) {
            return false;
        }
    }
    public function __construct(
        string $contents,
        protected readonly ?Logger_Interface $logger = new Quiet_Logger(),
        ?Uri_Interface $source_url = null,
        /**
         * A map used to map source spans in the text being parsed back to their
         * original locations in the source file, if this isn't being parsed directly
         * from source.
         */
        private readonly ?Interpolation_Map $interpolation_map = null
    )
    {
        $this->scanner = new String_Scanner($contents, $source_url);
    }
    /**
     * @throws SassFormatException
     */
    private function do_parse_identifier(): string
    {
        return $this->wrap_span_format_exception(function (): string {
            $result = $this->identifier();
            $this->scanner->expect_done();
            return $result;
        });
    }
    /**
     * Consumes whitespace, including any comments.
     */
    protected function whitespace(): void
    {
        do {
            $this->whitespace_without_comments();
        } while ($this->scan_comment());
    }
    /**
     * Consumes whitespace, but not comments.
     */
    protected function whitespace_without_comments(): void
    {
        while (!$this->scanner->is_done() && Character::is_whitespace($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
    }
    /**
     * Consumes spaces and tabs.
     */
    protected function spaces(): void
    {
        while (!$this->scanner->is_done() && Character::is_space_or_tab($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
    }
    /**
     * Consumes and ignores a comment if possible.
     *
     * Returns whether the comment was consumed.
     */
    protected function scan_comment(): bool
    {
        if ($this->scanner->peek_char() !== '/') {
            return false;
        }
        $next = $this->scanner->peek_char(1);
        if ($next === '/') {
            return $this->silent_comment();
        }
        if ($next === '*') {
            $this->loud_comment();
            return true;
        }
        return false;
    }
    /**
     * Like {@see whitespace}, but throws an error if no whitespace is consumed.
     */
    protected function expect_whitespace(): void
    {
        if ($this->scanner->is_done() || !(Character::is_whitespace($this->scanner->peek_char()) || $this->scan_comment())) {
            $this->scanner->error('Expected whitespace.');
        }
        $this->whitespace();
    }
    /**
     * Consumes and ignores a single silent (Sass-style) comment, not including
     * the trailing newline.
     *
     * Returns whether the comment was consumed.
     */
    protected function silent_comment(): bool
    {
        $this->scanner->expect('//');
        while (!$this->scanner->is_done() && !Character::is_newline($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
        return true;
    }
    /**
     * Consumes and ignores a loud (CSS-style) comment.
     */
    protected function loud_comment(): void
    {
        $this->scanner->expect('/*');
        while (true) {
            $next = $this->scanner->read_char();
            if ($next !== '*') {
                continue;
            }
            do {
                $next = $this->scanner->read_char();
            } while ($next === '*');
            if ($next === '/') {
                break;
            }
        }
    }
    /**
     * Consumes a plain CSS identifier.
     *
     * If $normalize is `true`, this converts underscores into hyphens.
     *
     * If $unit is `true`, this doesn't parse a `-` followed by a digit. This
     * ensures that `1px-2px` parses as subtraction rather than the unit
     * `px-2px`.
     */
    protected function identifier(bool $normalize = false, bool $unit = false): string
    {
        $text = '';
        if ($this->scanner->scan_char('-')) {
            $text .= '-';
            if ($this->scanner->scan_char('-')) {
                $text .= '-';
                return $text . $this->consume_identifier_body($normalize, $unit);
            }
        }
        $first = $this->scanner->peek_char();
        if ($first === null) {
            $this->scanner->error('Expected identifier.');
        }
        if ($normalize && $first === '_') {
            $this->scanner->read_char();
            $text .= '-';
        } elseif (Character::is_name_start($first)) {
            $text .= $this->scanner->read_utf8char();
        } elseif ($first === '\\') {
            $text .= $this->escape(true);
        } else {
            $this->scanner->error('Expected identifier.');
        }
        return $text . $this->consume_identifier_body($normalize, $unit);
    }
    /**
     * Consumes a chunk of a plain CSS identifier after the name start.
     */
    public function identifier_body(): string
    {
        $text = $this->consume_identifier_body();
        if ($text === '') {
            $this->scanner->error('Expected identifier body.');
        }
        return $text;
    }
    private function consume_identifier_body(bool $normalize = false, bool $unit = false): string
    {
        $text = '';
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            if ($unit && $next === '-') {
                $second = $this->scanner->peek_char(1);
                if ($second !== null && ($second === '.' || Character::is_digit($second))) {
                    break;
                }
                $text .= $this->scanner->read_char();
            } elseif ($normalize && $next === '_') {
                $this->scanner->read_char();
                $text .= '-';
            } elseif (Character::is_name($next)) {
                $text .= $this->scanner->read_utf8char();
            } elseif ($next === '\\') {
                $text .= $this->escape();
            } else {
                break;
            }
        }
        return $text;
    }
    /**
     * Consumes a plain CSS string.
     *
     * This returns the parsed contents of the string—that is, it doesn't include
     * quotes and its escapes are resolved.
     */
    protected function string(): string
    {
        $quote = $this->scanner->read_char();
        if ($quote !== '"' && $quote !== "'") {
            $this->scanner->error('Expected string.');
        }
        $buffer = '';
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === $quote) {
                $this->scanner->read_char();
                break;
            }
            if ($next === null || Character::is_newline($next)) {
                $this->scanner->error("Expected {$quote}.");
            }
            if ($next === '\\') {
                $second = $this->scanner->peek_char(1);
                if ($second !== null && Character::is_newline($second)) {
                    $this->scanner->read_char();
                    $this->scanner->read_char();
                } else {
                    $buffer .= $this->escape_character();
                }
            } else {
                $buffer .= $this->scanner->read_utf8char();
            }
        }
        return $buffer;
    }
    /**
     * Consumes and returns a natural number (that is, a non-negative integer) as a double.
     *
     * Doesn't support scientific notation.
     */
    protected function natural_number(): float
    {
        $first = $this->scanner->read_char();
        if (!Character::is_digit($first)) {
            $this->scanner->error('Expected digit.', $this->scanner->get_position() - 1);
        }
        $number = (float) intval($first);
        while (Character::is_digit($this->scanner->peek_char())) {
            $number *= 10;
            $number += intval($this->scanner->read_char());
        }
        return $number;
    }
    /**
     * Consumes tokens until it reaches a top-level `";"`, `")"`, `"]"`,
     * or `"}"` and returns their contents as a string.
     *
     * If $allowEmpty is `false` (the default), this requires at least one token.
     */
    protected function declaration_value(bool $allow_empty = false): string
    {
        $buffer = '';
        $brackets = [];
        $wrote_newline = false;
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            switch ($next) {
                case '\\':
                    $buffer .= $this->escape(true);
                    $wrote_newline = false;
                    break;
                case '"':
                case "'":
                    $buffer .= $this->raw_text($this->string(...));
                    $wrote_newline = false;
                    break;
                case '/':
                    if ($this->scanner->peek_char(1) === '*') {
                        $buffer .= $this->raw_text($this->loud_comment(...));
                    } else {
                        $buffer .= $this->scanner->read_char();
                    }
                    $wrote_newline = false;
                    break;
                case ' ':
                case "\t":
                    $second = $this->scanner->peek_char(1);
                    if ($wrote_newline || $second === null || !Character::is_whitespace($second)) {
                        $buffer .= ' ';
                    }
                    $this->scanner->read_char();
                    break;
                case "\n":
                case "\r":
                case "\f":
                    $prev = $this->scanner->peek_char(-1);
                    if ($prev === null || !Character::is_newline($prev)) {
                        $buffer .= "\n";
                    }
                    $this->scanner->read_char();
                    $wrote_newline = true;
                    break;
                case '(':
                case '{':
                case '[':
                    $buffer .= $next;
                    $brackets[] = Character::opposite($this->scanner->read_char());
                    $wrote_newline = false;
                    break;
                case ')':
                case '}':
                case ']':
                    if (empty($brackets)) {
                        break 2;
                    }
                    $buffer .= $next;
                    $this->scanner->expect_char(array_pop($brackets));
                    $wrote_newline = false;
                    break;
                case ';':
                    if (empty($brackets)) {
                        break 2;
                    }
                    $buffer .= $this->scanner->read_char();
                    break;
                case 'u':
                case 'U':
                    $url = $this->try_url();
                    if ($url !== null) {
                        $buffer .= $url;
                    } else {
                        $buffer .= $this->scanner->read_char();
                    }
                    $wrote_newline = false;
                    break;
                default:
                    if ($this->looking_at_identifier()) {
                        $buffer .= $this->identifier();
                    } else {
                        $buffer .= $this->scanner->read_utf8char();
                    }
                    $wrote_newline = false;
                    break;
            }
        }
        if (!empty($brackets)) {
            $this->scanner->expect_char(array_pop($brackets));
        }
        if (!$allow_empty && $buffer === '') {
            $this->scanner->error('Expected token.');
        }
        return $buffer;
    }
    /**
     * Consumes a `url()` token if possible, and returns `null` otherwise.
     */
    protected function try_url(): ?string
    {
        $start = $this->scanner->get_position();
        if (!$this->scan_identifier('url')) {
            return null;
        }
        if (!$this->scanner->scan_char('(')) {
            $this->scanner->set_position($start);
            return null;
        }
        $this->whitespace();
        $buffer = 'url(';
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            $next_char_code = \ord($next);
            if ($next === '\\') {
                $buffer .= $this->escape();
            } elseif ($next === '%' || $next === '&' || $next === '#' || $next_char_code >= \ord('*') && $next_char_code <= \ord('~') || $next_char_code >= 0x80) {
                $buffer .= $this->scanner->read_utf8char();
            } elseif (Character::is_whitespace($next)) {
                $this->whitespace();
                if ($this->scanner->peek_char() !== ')') {
                    break;
                }
            } elseif ($next === ')') {
                return $buffer . $this->scanner->read_char();
            } else {
                break;
            }
        }
        $this->scanner->set_position($start);
        return null;
    }
    /**
     * Consumes a Sass variable name, and returns its name without the dollar sign.
     */
    protected function variable_name(): string
    {
        $this->scanner->expect_char('$');
        return $this->identifier(true);
    }
    /**
     * Consumes an escape sequence and returns the text that defines it.
     *
     * If $identifierStart is true, this normalizes the escape sequence as
     * though it were at the beginning of an identifier.
     */
    protected function escape(bool $identifier_start = false): string
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('\\');
        $first = $this->scanner->peek_char();
        if ($first === null) {
            $this->scanner->error('Expected escape sequence.');
        }
        if (Character::is_newline($first)) {
            $this->scanner->error('Expected escape sequence.');
        }
        if (Character::is_hex($first)) {
            $value = 0;
            for ($i = 0; $i < 6; $i++) {
                $next = $this->scanner->peek_char();
                if ($next === null || !Character::is_hex($next)) {
                    break;
                }
                $value *= 16;
                $value += hexdec($this->scanner->read_char());
                assert(\is_int($value));
            }
            $this->scan_char_if(Character::is_whitespace(...));
            $value_text = mb_chr($value, 'UTF-8');
        } else {
            $value_text = $this->scanner->read_utf8char();
            $value = mb_ord($value_text, 'UTF-8');
        }
        if ($value_text === false) {
            $this->scanner->error('Invalid Unicode code point.', $start, $this->scanner->get_position() - $start);
        }
        if ($identifier_start ? Character::is_name_start($value_text) : Character::is_name($value_text)) {
            if ($value > 0x10ffff) {
                $this->scanner->error('Invalid Unicode code point.', $start, $this->scanner->get_position() - $start);
            }
            return $value_text;
        }
        if ($value <= 0x1f || $value_text === "" || $identifier_start && Character::is_digit($value_text)) {
            $hex_value_text = $value === 0 ? '0' : ltrim(bin2hex($value_text), '0');
            return '\\' . $hex_value_text . ' ';
        }
        return '\\' . $value_text;
    }
    /**
     * Consumes an escape sequence and returns the character it represents.
     */
    protected function escape_character(): string
    {
        return Parser_Util::consume_escaped_character($this->scanner);
    }
    /**
     * @param callable(string): bool $condition
     *
     * @param-immediately-invoked-callable $condition
     *
     * @phpstan-impure
     */
    protected function scan_char_if(callable $condition): bool
    {
        $next = $this->scanner->peek_char();
        if ($next === null || !$condition($next)) {
            return false;
        }
        $this->scanner->read_char();
        return true;
    }
    /**
     * Consumes the next character or escape sequence if it matches $character.
     *
     * Matching will be case-insensitive unless $caseSensitive is true.
     * When matching case-insensitively, $character must be passed in lowercase.
     *
     * This only supports ASCII identifier characters.
     */
    protected function scan_ident_char(string $character, bool $case_sensitive = false): bool
    {
        $matches = function (string $actual) use ($character, $case_sensitive): bool {
            if ($case_sensitive) {
                return $actual === $character;
            }
            return \strtolower($actual) === $character;
        };
        $next = $this->scanner->peek_char();
        if ($next !== null && $matches($next)) {
            $this->scanner->read_char();
            return true;
        }
        if ($next === '\\') {
            $start = $this->scanner->get_position();
            if ($matches($this->escape_character())) {
                return true;
            }
            $this->scanner->set_position($start);
        }
        return false;
    }
    /**
     * Consumes the next character or escape sequence and asserts it matches $char.
     *
     * Matching will be case-insensitive unless $caseSensitive is true.
     * When matching case-insensitively, $char must be passed in lowercase.
     *
     * This only supports ASCII identifier characters.
     */
    protected function expect_ident_char(string $char, bool $case_sensitive = false): void
    {
        if ($this->scan_ident_char($char, $case_sensitive)) {
            return;
        }
        $this->scanner->error("Expected \"{$char}\".");
    }
    /**
     * Returns whether the scanner is immediately before a number.
     *
     * This follows [the CSS algorithm][].
     *
     * [the CSS algorithm]: https://drafts.csswg.org/css-syntax-3/#starts-with-a-number
     */
    protected function looking_at_number(): bool
    {
        $first = $this->scanner->peek_char();
        if ($first === null) {
            return false;
        }
        if (Character::is_digit($first)) {
            return true;
        }
        if ($first === '.') {
            $second = $this->scanner->peek_char(1);
            return $second !== null && Character::is_digit($second);
        }
        if ($first === '+' || $first === '-') {
            $second = $this->scanner->peek_char(1);
            if ($second === null) {
                return false;
            }
            if (Character::is_digit($second)) {
                return true;
            }
            if ($second !== '.') {
                return false;
            }
            $third = $this->scanner->peek_char(2);
            return $third !== null && Character::is_digit($third);
        }
        return false;
    }
    /**
     * Returns whether the scanner is immediately before a plain CSS identifier.
     *
     * If $forward is passed, this looks that many characters forward instead.
     *
     * This is based on [the CSS algorithm][], but it assumes all backslashes
     * start escapes.
     *
     * [the CSS algorithm]: https://drafts.csswg.org/css-syntax-3/#would-start-an-identifier
     */
    protected function looking_at_identifier(int $forward = 0): bool
    {
        $first = $this->scanner->peek_char($forward);
        if ($first === null) {
            return false;
        }
        if ($first === '\\' || Character::is_name_start($first)) {
            return true;
        }
        if ($first !== '-') {
            return false;
        }
        $second = $this->scanner->peek_char($forward + 1);
        if ($second === null) {
            return false;
        }
        return $second === '\\' || $second === '-' || Character::is_name_start($second);
    }
    /**
     * Returns whether the scanner is immediately before a sequence of characters
     * that could be part of a plain CSS identifier body.
     */
    protected function looking_at_identifier_body(): bool
    {
        $next = $this->scanner->peek_char();
        return $next !== null && ($next === '\\' || Character::is_name($next));
    }
    /**
     * Consumes an identifier if its name exactly matches $text.
     *
     * When matching case-insensitively, $text must be passed in lowercase.
     *
     * This only supports ASCII identifiers.
     */
    protected function scan_identifier(string $text, bool $case_sensitive = false): bool
    {
        if (!$this->looking_at_identifier()) {
            return false;
        }
        $start = $this->scanner->get_position();
        if ($this->consume_identifier($text, $case_sensitive) && !$this->looking_at_identifier_body()) {
            return true;
        }
        $this->scanner->set_position($start);
        return false;
    }
    /**
     * Returns whether an identifier whose name exactly matches $text is at the
     * current scanner position.
     *
     * This doesn't move the scan pointer forward
     */
    protected function matches_identifier(string $text, bool $case_sensitive = false): bool
    {
        if (!$this->looking_at_identifier()) {
            return false;
        }
        $start = $this->scanner->get_position();
        $result = $this->consume_identifier($text, $case_sensitive) && !$this->looking_at_identifier_body();
        $this->scanner->set_position($start);
        return $result;
    }
    /**
     * Consumes $text as an identifier, but doesn't verify whether there's
     * additional identifier text afterwards.
     *
     * Returns `true` if the full $text is consumed and `false` otherwise, but
     * doesn't reset the scan pointer.
     */
    private function consume_identifier(string $text, bool $case_sensitive): bool
    {
        for ($i = 0; $i < \strlen($text); $i++) {
            if (!$this->scan_ident_char($text[$i], $case_sensitive)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Consumes an identifier asserts that its name exactly matches $text.
     *
     * When matching case-insensitively, $text must be passed in lowercase.
     *
     * This only supports ASCII identifiers.
     */
    protected function expect_identifier(string $text, ?string $name = null, bool $case_sensitive = false): void
    {
        $name ??= "\"{$text}\"";
        $start = $this->scanner->get_position();
        for ($i = 0; $i < \strlen($text); $i++) {
            if ($this->scan_ident_char($text[$i], $case_sensitive)) {
                continue;
            }
            $this->scanner->error("Expected {$name}.", $start);
        }
        if (!$this->looking_at_identifier_body()) {
            return;
        }
        $this->scanner->error("Expected {$name}.", $start);
    }
    /**
     * Runs $consumer and returns the source text that it consumes.
     *
     * @param callable(): (mixed|void) $consumer
     *
     * @param-immediately-invoked-callable $consumer
     */
    protected function raw_text(callable $consumer): string
    {
        $start = $this->scanner->get_position();
        $consumer();
        return $this->scanner->substring($start);
    }
    /**
     * Like {@see StringScanner::spanFrom()} but passes the span through {@see $interpolationMap} if it's available.
     */
    protected function span_from(int $position): File_Span
    {
        $span = $this->scanner->span_from($position);
        if ($this->interpolation_map === null) {
            return $span;
        }
        $interpolation_map = $this->interpolation_map;
        return new Lazy_File_Span(static fn(): \Source_Span\File_Span => $interpolation_map->map_span($span));
    }
    /**
     * Prints a warning to standard error, associated with $span.
     */
    protected function warn(string $message, File_Span $span): void
    {
        $this->logger->warn($message, null, $span);
    }
    /**
     * Throws an error associated with $position.
     *
     * @throws FormatException
     */
    protected function error(string $message, File_Span $span, ?\Throwable $previous = null): never
    {
        throw new Format_Exception($message, $span, $previous);
    }
    /**
     * Runs $callback and wraps any {@see FormatException} it throws in a
     * {@see SassFormatException}
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     *
     * @throws SassFormatException
     */
    protected function wrap_span_format_exception(callable $callback)
    {
        try {
            try {
                return $callback();
            } catch (Format_Exception $e) {
                if ($this->interpolation_map === null) {
                    throw $e;
                }
                throw $this->interpolation_map->map_exception($e);
            }
        } catch (Multi_Source_Format_Exception $error) {
            $span = $error->get_span();
            $secondary_spans = $error->secondary_spans;
            if (0 === stripos($error->get_message(), 'expected')) {
                $span = $this->adjust_exception_span($span);
                $secondary_spans = array_map($this->adjust_exception_span(...), $secondary_spans);
            }
            throw new Multi_Span_Sass_Format_Exception($error->get_message(), $span, $error->primary_label, $secondary_spans, $error);
        } catch (Format_Exception $error) {
            $span = $error->get_span();
            if (0 === stripos($error->get_message(), 'expected')) {
                $span = $this->adjust_exception_span($span);
            }
            throw new Simple_Sass_Format_Exception($error->get_message(), $span, $error);
        }
    }
    /**
     * Moves span to {@see firstNewlineBefore} if necessary.
     */
    private function adjust_exception_span(File_Span $span): File_Span
    {
        if ($span->get_length() > 0) {
            return $span;
        }
        $start = $this->first_newline_before($span->get_start());
        if ($start === $span->get_start()) {
            return $span;
        }
        return $start->point_span();
    }
    /**
     * If $location is separated from the previous non-whitespace character in
     * `$scanner->getString()` by one or more newlines, returns the location of the last
     * separating newline.
     *
     * Otherwise returns $location.
     *
     * This helps avoid missing token errors pointing at the next closing bracket
     * rather than the line where the problem actually occurred.
     */
    private function first_newline_before(File_Location $location): File_Location
    {
        $text = $location->get_file()->get_text(0, $location->get_offset());
        $index = $location->get_offset() - 1;
        $last_newline = null;
        while ($index >= 0) {
            $char = $text[$index];
            if (!Character::is_whitespace($char)) {
                return $last_newline === null ? $location : $location->get_file()->location($last_newline);
            }
            if (Character::is_newline($char)) {
                $last_newline = $index;
            }
            $index--;
        }
        // If the document *only* contains whitespace before $location, always
        // return $location.
        return $location;
    }
}