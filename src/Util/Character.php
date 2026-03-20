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
final class Character
{
    /**
     * The difference between upper- and lowercase ASCII letters.
     *
     * `0b100000` can be bitwise-ORed with uppercase ASCII letters to get their
     * lowercase equivalents.
     */
    private const ASCII_CASE_BIT = 0x20;
    /**
     * Returns whether $character is an ASCII whitespace character.
     */
    public static function is_whitespace(?string $character): bool
    {
        return $character === ' ' || $character === "\t" || $character === "\n" || $character === "\r" || $character === "\f";
    }
    /**
     * Returns whether $character is a space or a tab character.
     */
    public static function is_space_or_tab(?string $character): bool
    {
        return $character === ' ' || $character === "\t";
    }
    /**
     * Returns whether $character is an ASCII newline character.
     */
    public static function is_newline(?string $character): bool
    {
        return $character === "\n" || $character === "\r" || $character === "\f";
    }
    /**
     * Returns whether $character is a letter or a number.
     */
    public static function is_alphanumeric(string $character): bool
    {
        if (self::is_alphabetic($character)) {
            return true;
        }
        return self::is_digit($character);
    }
    /**
     * Returns whether $character is a letter.
     */
    public static function is_alphabetic(string $character): bool
    {
        $char_code = \ord($character[0]);
        return $char_code >= \ord('a') && $char_code <= \ord('z') || $char_code >= \ord('A') && $char_code <= \ord('Z');
    }
    /**
     * Returns whether $character is a digit.
     */
    public static function is_digit(?string $character): bool
    {
        if ($character === null) {
            return false;
        }
        $char_code = \ord($character[0]);
        return $char_code >= \ord('0') && $char_code <= \ord('9');
    }
    /**
     * Returns whether $character is legal as the start of a Sass identifier.
     */
    public static function is_name_start(string $character): bool
    {
        if ($character === '_') {
            return true;
        }
        if (self::is_alphabetic($character)) {
            return true;
        }
        return \ord($character[0]) >= 0x80;
    }
    /**
     * Returns whether $character is legal in the body of a Sass identifier.
     */
    public static function is_name(string $character): bool
    {
        if (self::is_name_start($character)) {
            return true;
        }
        if (self::is_digit($character)) {
            return true;
        }
        return $character === '-';
    }
    /**
     * Returns whether $character is a hexadecimal digit.
     */
    public static function is_hex(?string $character): bool
    {
        if ($character === null) {
            return false;
        }
        if (self::is_digit($character)) {
            return true;
        }
        $char_code = \ord($character[0]);
        if ($char_code >= \ord('a') && $char_code <= \ord('f')) {
            return true;
        }
        if ($char_code >= \ord('A') && $char_code <= \ord('F')) {
            return true;
        }
        return false;
    }
    /**
     * Returns whether $identifier is module-private.
     *
     * Assumes $identifier is a valid Sass identifier.
     */
    public static function is_private(string $identifier): bool
    {
        $first = $identifier[0];
        return $first === '-' || $first === '_';
    }
    /**
     * Assumes that $character is a left-hand brace-like character, and returns
     * the right-hand version.
     */
    public static function opposite(string $character): string
    {
        return match ($character) {
            '(' => ')',
            '{' => '}',
            '[' => ']',
            default => throw new \InvalidArgumentException(sprintf('Expected a brace character. Got "%s"', $character)),
        };
    }
    public static function equals_ignore_case(string $character1, string $character2): bool
    {
        if ($character1 === $character2) {
            return true;
        }
        // If this check fails, the characters are definitely different. If it
        // succeeds *and* either character is an ASCII letter, they're equivalent.
        if ((\ord($character1[0]) ^ \ord($character2[0])) !== self::ASCII_CASE_BIT) {
            return false;
        }
        // Now we just need to verify that one of the characters is an ASCII letter.
        $upper_case1 = \ord($character1[0]) & ~self::ASCII_CASE_BIT;
        return $upper_case1 >= \ord('A') && $upper_case1 <= \ord('Z');
    }
}