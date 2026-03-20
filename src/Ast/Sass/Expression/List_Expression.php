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
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A list literal.
 *
 * @internal
 */
final class List_Expression implements Expression
{
    private readonly File_Span $span;
    /**
     * ListExpression constructor.
     *
     * @param list<Expression> $contents
     */
    public function __construct(private readonly array $contents, private readonly List_Separator $separator, File_Span $span, private readonly bool $brackets = false)
    {
        $this->span = $span;
    }
    /**
     * @return list<Expression>
     */
    public function get_contents(): array
    {
        return $this->contents;
    }
    public function get_separator(): List_Separator
    {
        return $this->separator;
    }
    public function has_brackets(): bool
    {
        return $this->brackets;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_list_expression($this);
    }
    public function __toString(): string
    {
        $buffer = '';
        if ($this->has_brackets()) {
            $buffer .= '[';
        } elseif (\count($this->contents) === 0 || \count($this->contents) === 1 && $this->separator === List_Separator::COMMA) {
            $buffer .= '(';
        }
        $buffer .= implode($this->separator === List_Separator::COMMA ? ', ' : ' ', array_map(fn(\Scss_Php\Scss_Php\Ast\Sass\Expression $element): string => $this->element_needs_parens($element) ? "({$element})" : (string) $element, $this->contents));
        if ($this->has_brackets()) {
            $buffer .= ']';
        } elseif (\count($this->contents) === 0) {
            $buffer .= ')';
        } elseif (\count($this->contents) === 1 && $this->separator === List_Separator::COMMA) {
            $buffer .= ',)';
        }
        return $buffer;
    }
    /**
     * Returns whether $expression, contained in $this, needs parentheses when
     * printed as Sass source.
     */
    private function element_needs_parens(Expression $expression): bool
    {
        if ($expression instanceof List_Expression) {
            if (\count($expression->contents) < 2) {
                return false;
            }
            if ($expression->brackets) {
                return false;
            }
            return $this->separator === List_Separator::COMMA ? $expression->separator === List_Separator::COMMA : $expression->separator !== List_Separator::UNDECIDED;
        }
        if ($this->separator !== List_Separator::SPACE) {
            return false;
        }
        if ($expression instanceof Unary_Operation_Expression) {
            if ($expression->get_operator() === Unary_Operator::PLUS) {
                return true;
            }
            return $expression->get_operator() === Unary_Operator::MINUS;
        }
        return false;
    }
}