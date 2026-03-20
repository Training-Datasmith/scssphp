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
use Source_Span\File_Location;
use Source_Span\File_Span;
use Source_Span\Source_File;
/**
 * A port of Dart's string_scanner package to be used by the parser.
 *
 * The scanner only supports UTF-8 strings.
 *
 * Differences with Dart:
 * - reading a character is reading a byte, not a UTF-16 code unit (as PHP strings are not UTF-16). The
 *   {@see readUtf8Char} method can be used to consume a UTF-8 char.
 * - characters are represented as a single-char string, not as an integer with their UTF-16 char code
 * - offsets are based on bytes, not on UTF-16 code units. In practice, parsing Sass generally needs
 *   to peak following chars only when already knowing that the current char is an ASCII one, which
 *   makes this safe. When this assumption does not hold anymore, a different logic should be used
 * - as strings and regexp cannot be used interchangeably in PHP (in Dart, regexps are a different
 *   object, and both String and Regexp are implementing a Pattern interface for matching), the scanner
 *   exposes supports only strings in scan() and expect(). Should we need support for regexps, a
 *   separate method will be added.
 *
 * @internal
 */
class String_Scanner
{
    private int $position = 0;
    private readonly Source_File $source_file;
    private ?int $last_match_start = null;
    private ?int $last_match_position = null;
    public function __construct(private readonly string $string, ?Uri_Interface $source_url = null)
    {
        $this->source_file = Source_File::from_string($this->string, $source_url);
    }
    public function get_string(): string
    {
        return $this->string;
    }
    public function get_position(): int
    {
        return $this->position;
    }
    public function set_position(int $position): void
    {
        $this->position = $position;
        $this->last_match_start = null;
    }
    public function span_from(int $start, ?int $end = null): File_Span
    {
        return $this->source_file->span($start, $end ?? $this->position);
    }
    /**
     * The current location of the scanner.
     */
    public function get_location(): File_Location
    {
        return $this->source_file->location($this->position);
    }
    /**
     * Returns an empty span at the current location.
     */
    public function get_empty_span(): File_Span
    {
        return $this->source_file->span($this->position, $this->position);
    }
    public function is_done(): bool
    {
        return $this->position === \strlen($this->string);
    }
    /**
     * @throws FormatException if the end of the string is reached
     *
     * @phpstan-impure
     */
    public function read_char(): string
    {
        if ($this->position === \strlen($this->string)) {
            $this->fail('more input');
        }
        return $this->string[$this->position++];
    }
    /**
     * @throws FormatException if the end of the string is reached
     *
     * @phpstan-impure
     */
    public function read_utf8char(): string
    {
        if ($this->position === \strlen($this->string)) {
            $this->fail('more input');
        }
        if (\ord($this->string[$this->position]) < 0x80) {
            return $this->string[$this->position++];
        }
        if (!preg_match('/./usA', $this->string, $m, 0, $this->position)) {
            $this->fail('utf-8 char');
        }
        $this->position += \strlen($m[0]);
        return $m[0];
    }
    /**
     * Consumes the next character in the string if it is the provided character.
     *
     * @return bool Whether the character was consumed.
     *
     * @phpstan-impure
     */
    public function scan_char(string $char): bool
    {
        if ($this->position === \strlen($this->string)) {
            return false;
        }
        if ($this->string[$this->position] !== $char) {
            return false;
        }
        ++$this->position;
        return true;
    }
    /**
     * Consumes the provided string if it appears at the current position.
     *
     * @return bool Whether the string was consumed.
     *
     * @phpstan-impure
     */
    public function scan(string $string): bool
    {
        if (!$this->matches($string)) {
            return false;
        }
        $this->position += \strlen($string);
        $this->last_match_position = $this->position;
        return true;
    }
    /**
     * Returns whether or not the provided string appears at the current position.
     *
     * This doesn't move the scan pointer forward.
     */
    public function matches(string $string): bool
    {
        if ($this->position - 1 + \strlen($string) >= \strlen($this->string)) {
            return false;
        }
        if (substr($this->string, $this->position, \strlen($string)) === $string) {
            $this->last_match_start = $this->position;
            $this->last_match_position = $this->position;
            return true;
        }
        return false;
    }
    /**
     * If the next character in the string is $character, consumes it.
     *
     * If $character could not be consumed, throws an exception
     * describing the position of the failure. $name is used in this error as
     * the expected name of the character being matched; if it's `null`, the
     * character itself is used instead.
     *
     * @throws FormatException
     *
     * @phpstan-impure
     */
    public function expect_char(string $character, ?string $name = null): void
    {
        if ($this->scan_char($character)) {
            return;
        }
        if ($name === null) {
            $name = '"' . $character . '"';
        }
        $this->fail($name);
    }
    /**
     * @throws FormatException
     *
     * @phpstan-impure
     */
    public function expect(string $string): void
    {
        if ($this->scan($string)) {
            return;
        }
        $this->fail('"' . $string . '"');
    }
    /**
     * @throws FormatException
     */
    public function expect_done(): void
    {
        if ($this->is_done()) {
            return;
        }
        $this->fail('no more input');
    }
    /**
     * Returns the character at the given offset of the current position.
     *
     * The offset can be negative to peek already seen characters.
     * Returns null if the offset goes out of range.
     * This does not affect the position or the last match.
     */
    public function peek_char(int $offset = 0): ?string
    {
        $pos = $this->position + $offset;
        if ($pos < 0 || $pos >= \strlen($this->string)) {
            return null;
        }
        return $this->string[$pos];
    }
    /**
     * Returns the substring of the string between $start and $end (excluded).
     *
     * $end defaults to the current position.
     */
    public function substring(int $start, ?int $end = null): string
    {
        if ($end === null) {
            $end = $this->position;
        }
        if ($end < $start) {
            return '';
        }
        return substr($this->string, $start, $end - $start);
    }
    /**
     * The scanner's current (zero-based) line number.
     */
    public function get_line(): int
    {
        return $this->source_file->get_line($this->position);
    }
    /**
     * The scanner's current (zero-based) column number.
     */
    public function get_column(): int
    {
        return $this->source_file->get_column($this->position);
    }
    /**
     * @throws FormatException
     */
    public function error(string $message, ?int $position = null, ?int $length = null): never
    {
        if ($position === null && $length === null && $this->get_last_match_start() !== null) {
            \assert($this->last_match_start !== null);
            $position = $this->last_match_start;
            $length = $this->position - $position;
        }
        $position ??= $this->position;
        $length ??= 0;
        $span = $this->source_file->span($position, $position + $length);
        throw new Format_Exception($message, $span);
    }
    private function get_last_match_start(): ?int
    {
        // Lazily unset $this->lastMatchStart so that we avoid extra assignments in
        // character-by-character methods that are used in core loops.
        if ($this->last_match_position !== $this->position) {
            $this->last_match_start = null;
        }
        return $this->last_match_start;
    }
    /**
     * @throws FormatException
     */
    private function fail(string $message): never
    {
        $this->error("expected {$message}.");
    }
}