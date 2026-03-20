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
namespace Scss_Php\Scss_Php\Ast\Css;

use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Ast\Selector\Combinator;
use Scss_Php\Scss_Php\Util\Equatable;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Source_Span\File_Span;
/**
 * A value in a plain CSS tree.
 *
 * This is used to associate a span with a value that doesn't otherwise track
 * its span. It has value equality semantics.
 *
 * @template-covariant T of string|\Stringable|array<string|\Stringable>|Combinator|null
 *
 * @internal
 */
final class Css_Value implements Ast_Node, Equatable
{
    private readonly File_Span $span;
    /**
     * @param T $value
     */
    public function __construct(private readonly mixed $value, File_Span $span)
    {
        $this->span = $span;
    }
    /**
     * @return T
     */
    public function get_value(): mixed
    {
        return $this->value;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function equals(object $other): bool
    {
        return $other instanceof Css_Value && Equatable_Util::equals($this->value, $other->value);
    }
    public function __toString(): string
    {
        if ($this->value instanceof Combinator) {
            return $this->value->get_text();
        }
        if (\is_array($this->value)) {
            return implode('', $this->value);
        }
        return (string) $this->value;
    }
}