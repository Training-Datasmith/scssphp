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
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A binary operator, as in `1 + 2` or `$this and $other`.
 *
 * @internal
 */
final class Binary_Operation_Expression implements Expression
{
    /**
     * Whether this is a dividedBy operation that may be interpreted as slash-separated numbers.
     */
    private bool $allows_slash = false;
    public function __construct(private readonly Binary_Operator $operator, private readonly Expression $left, private readonly Expression $right)
    {
    }
    /**
     * Creates a dividedBy operation that may be interpreted as slash-separated numbers.
     */
    public static function slash(Expression $left, Expression $right): self
    {
        $operation = new self(Binary_Operator::DIVIDED_BY, $left, $right);
        $operation->allows_slash = true;
        return $operation;
    }
    public function get_operator(): Binary_Operator
    {
        return $this->operator;
    }
    public function get_left(): Expression
    {
        return $this->left;
    }
    public function get_right(): Expression
    {
        return $this->right;
    }
    public function allows_slash(): bool
    {
        return $this->allows_slash;
    }
    public function get_span(): File_Span
    {
        $left = $this->left;
        while ($left instanceof Binary_Operation_Expression) {
            $left = $left->left;
        }
        $right = $this->right;
        while ($right instanceof Binary_Operation_Expression) {
            $right = $right->right;
        }
        $left_span = $left->get_span();
        $right_span = $right->get_span();
        return $left_span->expand($right_span);
    }
    /**
     * Returns the span that covers only {@see $operator}.
     *
     * @internal
     */
    public function get_operator_span(): File_Span
    {
        $left_span = $this->left->get_span();
        $right_span = $this->right->get_span();
        if ($left_span->get_file() === $right_span->get_file() && $left_span->get_end()->get_offset() < $right_span->get_start()->get_offset()) {
            return Span_Util::trim($left_span->get_file()->span($left_span->get_end()->get_offset(), $right_span->get_start()->get_offset()));
        }
        return $this->get_span();
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_binary_operation_expression($this);
    }
    public function __toString(): string
    {
        $buffer = '';
        $left_needs_parens = $this->left instanceof Binary_Operation_Expression && $this->left->get_operator()->get_precedence() < $this->operator->get_precedence() || $this->left instanceof List_Expression && !$this->left->has_brackets() && \count($this->left->get_contents()) > 1;
        if ($left_needs_parens) {
            $buffer .= '(';
        }
        $buffer .= $this->left;
        if ($left_needs_parens) {
            $buffer .= ')';
        }
        $buffer .= ' ';
        $buffer .= $this->operator->get_operator();
        $buffer .= ' ';
        $right_needs_parens = $this->right instanceof Binary_Operation_Expression && $this->right->get_operator()->get_precedence() <= $this->operator->get_precedence() && !($this->right->operator === $this->operator && $this->operator->is_associative()) || $this->right instanceof List_Expression && !$this->right->has_brackets() && \count($this->right->get_contents()) > 1;
        if ($right_needs_parens) {
            $buffer .= '(';
        }
        $buffer .= $this->right;
        if ($right_needs_parens) {
            $buffer .= ')';
        }
        return $buffer;
    }
}