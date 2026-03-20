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

/**
 * @internal
 */
final class String_Util
{
    /**
     * @param non-empty-array<string> $iter
     */
    public static function to_sentence(array $iter, string $conjunction = 'and'): string
    {
        if (\count($iter) === 1) {
            return $iter[array_key_first($iter)];
        }
        $last = array_pop($iter);
        return implode(', ', $iter) . ' ' . $conjunction . ' ' . $last;
    }
    /**
     * Returns $name if $number is 1, or the plural of $name otherwise.
     *
     * By default, this just adds "s" to the end of $name to get the plural. If
     * $plural is passed, that's used instead.
     */
    public static function pluralize(string $name, int $number, ?string $plural = null): string
    {
        if ($number === 1) {
            return $name;
        }
        if ($plural !== null) {
            return $plural;
        }
        return $name . 's';
    }
    public static function trim_ascii(string $string, bool $exclude_escape = false): string
    {
        $start = self::first_non_whitespace($string);
        if ($start === null) {
            return '';
        }
        $end = self::last_non_whitespace($string, $exclude_escape);
        assert($end !== null);
        return substr($string, $start, $end + 1);
    }
    public static function trim_ascii_right(string $string, bool $exclude_escape = false): string
    {
        $end = self::last_non_whitespace($string, $exclude_escape);
        if ($end === null) {
            return '';
        }
        return substr($string, 0, $end + 1);
    }
    /**
     * Returns the index of the first character in $string that's not ASCII
     * whitespace, or `null` if $string is entirely spaces.
     *
     * If $excludeEscape is `true`, this doesn't move past whitespace that's
     * included in a CSS escape.
     */
    private static function first_non_whitespace(string $string): ?int
    {
        for ($i = 0; $i < \strlen($string); $i++) {
            $char = $string[$i];
            if (!Character::is_whitespace($char)) {
                return $i;
            }
        }
        return null;
    }
    /**
     * Returns the index of the last character in $string that's not ASCII
     * whitespace, or `null` if $string is entirely spaces.
     *
     * If $excludeEscape is `true`, this doesn't move past whitespace that's
     * included in a CSS escape.
     */
    private static function last_non_whitespace(string $string, bool $exclude_escape = false): ?int
    {
        for ($i = \strlen($string) - 1; $i >= 0; $i--) {
            $char = $string[$i];
            if (!Character::is_whitespace($char)) {
                if ($exclude_escape && $i !== 0 && $i !== \strlen($string) && $char === '\\') {
                    return $i + 1;
                }
                return $i;
            }
        }
        return null;
    }
    /**
     * Returns whether $string1 and $string2 are equal, ignoring ASCII case.
     */
    public static function equals_ignore_case(?string $string1, string $string2): bool
    {
        if ($string1 === $string2) {
            return true;
        }
        if ($string1 === null) {
            return false;
        }
        return self::to_ascii_lower_case($string1) === self::to_ascii_lower_case($string2);
    }
    /**
     * Returns whether $string starts with $prefix, ignoring ASCII case.
     */
    public static function starts_with_ignore_case(string $string, string $prefix): bool
    {
        if (\strlen($string) < \strlen($prefix)) {
            return false;
        }
        for ($i = 0; $i < \strlen($prefix); $i++) {
            if (!Character::equals_ignore_case($string[$i], $prefix[$i])) {
                return false;
            }
        }
        return true;
    }
    /**
     * Converts all ASCII chars to lowercase in the input string.
     *
     * This does not use `strtolower` because `strtolower` is locale-dependant
     * rather than operating on ASCII.
     * Passing an input string in an encoding that it is not ASCII compatible is
     * unsupported, and will probably generate garbage.
     */
    public static function to_ascii_lower_case(string $string): string
    {
        return strtr($string, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }
    /**
     * Converts all ASCII chars to uppercase in the input string.
     *
     * This does not use `strtoupper` because `strtoupper` is locale-dependant
     * rather than operating on ASCII.
     * Passing an input string in an encoding that it is not ASCII compatible is
     * unsupported, and will probably generate garbage.
     */
    public static function to_ascii_upper_case(string $string): string
    {
        return strtr($string, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }
}