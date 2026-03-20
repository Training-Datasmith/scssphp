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
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A unary operator, as in `+$var` or `not fn()`.
 *
 * @internal
 */
final class Unary_Operation_Expression implements Expression
{
    private readonly File_Span $span;
    public function __construct(private readonly Unary_Operator $operator, private readonly Expression $operand, File_Span $span)
    {
        $this->span = $span;
    }
    public function get_operator(): Unary_Operator
    {
        return $this->operator;
    }
    public function get_operand(): Expression
    {
        return $this->operand;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_unary_operation_expression($this);
    }
    public function __toString(): string
    {
        $buffer = $this->operator->get_operator();
        if ($this->operator === Unary_Operator::NOT) {
            $buffer .= ' ';
        }
        $needs_parens = $this->operand instanceof Binary_Operation_Expression || $this->operand instanceof Unary_Operation_Expression || $this->operand instanceof List_Expression && !$this->operand->has_brackets() && \count($this->operand->get_contents()) > 1;
        if ($needs_parens) {
            $buffer .= '(';
        }
        $buffer .= $this->operand;
        if ($needs_parens) {
            $buffer .= ')';
        }
        return $buffer;
    }
}