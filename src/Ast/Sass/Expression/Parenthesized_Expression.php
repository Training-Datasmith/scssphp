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
 * An expression wrapped in parentheses.
 *
 * @internal
 */
final class Parenthesized_Expression implements Expression
{
    private readonly File_Span $span;
    public function __construct(private readonly Expression $expression, File_Span $span)
    {
        $this->span = $span;
    }
    public function get_expression(): Expression
    {
        return $this->expression;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_parenthesized_expression($this);
    }
    public function __toString(): string
    {
        return '(' . $this->expression . ')';
    }
}