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
/**
 * @internal
 */
final class Parser_Util
{
    /**
     * Consumes an escape sequence from $scanner and returns the character it
     * represents.
     */
    public static function consume_escaped_character(String_Scanner $scanner): string
    {
        // See https://drafts.csswg.org/css-syntax-3/#consume-escaped-code-point.
        $scanner->expect_char('\\');
        $first = $scanner->peek_char();
        if ($first === null) {
            return "�";
        }
        if (Character::is_newline($first)) {
            $scanner->error('Expected escape sequence.');
        }
        if (Character::is_hex($first)) {
            $value = 0;
            for ($i = 0; $i < 6; $i++) {
                $next = $scanner->peek_char();
                if ($next === null || !Character::is_hex($next)) {
                    break;
                }
                $value *= 16;
                $value += hexdec($scanner->read_char());
                assert(\is_int($value));
            }
            if (Character::is_whitespace($scanner->peek_char())) {
                $scanner->read_char();
            }
            if ($value === 0 || $value >= 0xd800 && $value <= 0xdfff || $value >= 0x10ffff) {
                return "�";
            }
            return mb_chr($value, 'UTF-8');
        }
        return $scanner->read_utf8char();
    }
}