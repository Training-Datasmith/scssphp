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
namespace Scss_Php\Scss_Php\Ast\Sass\Expression;

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Parser\Interpolation_Buffer;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A string literal.
 *
 * @internal
 */
final class String_Expression implements Expression
{
    public function __construct(private readonly Interpolation $text, private readonly bool $quotes = false)
    {
    }
    /**
     * Returns a string expression with no interpolation.
     */
    public static function plain(string $text, File_Span $span, bool $quotes = false): self
    {
        return new self(new Interpolation([$text], $span), $quotes);
    }
    /**
     * Returns Sass source for a quoted string that, when evaluated, will have
     * $text as its contents.
     */
    public static function quote_text(string $text): string
    {
        $quote = self::best_quote([$text]);
        $buffer = $quote;
        $buffer .= self::quote_inner_text($text, $quote, true);
        return $buffer . $quote;
    }
    /**
     * Interpolation that, when evaluated, produces the contents of this string.
     *
     * Unlike {@see asInterpolation}, escapes are resolved and quotes are not
     * included.
     * If this is a quoted string, escapes are resolved and quotes are not
     * included in this text (unlike {@see asInterpolation}). If it's an unquoted
     * string, escapes are *not* resolved.
     */
    public function get_text(): Interpolation
    {
        return $this->text;
    }
    public function has_quotes(): bool
    {
        return $this->quotes;
    }
    public function get_span(): File_Span
    {
        return $this->text->get_span();
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_string_expression($this);
    }
    public function as_interpolation(bool $static = false, ?string $quote = null): Interpolation
    {
        if (!$this->quotes) {
            return $this->text;
        }
        $quote ??= self::best_quote($this->text->get_contents());
        $buffer = new Interpolation_Buffer();
        $buffer->write($quote);
        foreach ($this->text->get_contents() as $value) {
            if ($value instanceof Expression) {
                $buffer->add($value);
            } else {
                $buffer->write(self::quote_inner_text($value, $quote, $static));
            }
        }
        $buffer->write($quote);
        return $buffer->build_interpolation($this->text->get_span());
    }
    private static function quote_inner_text(string $value, string $quote, bool $static = false): string
    {
        $buffer = '';
        $length = \strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            if (Character::is_newline($char)) {
                $buffer .= '\a';
                if ($i !== $length - 1) {
                    $next = $value[$i + 1];
                    if (Character::is_whitespace($next) || Character::is_hex($next)) {
                        $buffer .= ' ';
                    }
                }
            } else {
                if ($char === $quote || $char === '\\' || $static && $char === '#' && $i < $length - 1 && $value[$i + 1] === '{') {
                    $buffer .= '\\';
                }
                if (\ord($char) < 0x80) {
                    $buffer .= $char;
                } else {
                    if (!preg_match('/./usA', $value, $m, 0, $i)) {
                        throw new \UnexpectedValueException('Invalid UTF-8 char');
                    }
                    $buffer .= $m[0];
                    $i += \strlen($m[0]) - 1;
                    // skip over the extra bytes that have been processed.
                }
            }
        }
        return $buffer;
    }
    /**
     * @param array<string|Expression> $parts
     */
    private static function best_quote(array $parts): string
    {
        $contains_double_quote = false;
        foreach ($parts as $part) {
            if (!\is_string($part)) {
                continue;
            }
            if (str_contains($part, "'")) {
                return '"';
            }
            if (str_contains($part, '"')) {
                $contains_double_quote = true;
            }
        }
        return $contains_double_quote ? "'" : '"';
    }
    public function __toString(): string
    {
        return (string) $this->as_interpolation();
    }
}