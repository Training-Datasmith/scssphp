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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operator;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Interpolated_Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Number_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operator;
use Scss_Php\Scss_Php\Visitor\Replace_Expression_Visitor;
/**
 * A visitor that replaces constructs that can't be used in a calculation with
 * those that can.
 *
 * @internal
 */
final class Make_Expression_Calculation_Safe extends Replace_Expression_Visitor
{
    public function visit_binary_operation_expression(Binary_Operation_Expression $node): Expression
    {
        // `calc()` doesn't support `%` for modulo but Sass doesn't yet support the
        // `mod()` calculation function because there's no browser support, so we have
        // to work around it by wrapping the call in a Sass function.
        if ($node->get_operator() === Binary_Operator::MODULO) {
            return new Function_Expression('max', new Argument_Invocation([$node], [], $node->get_span()), $node->get_span(), 'math');
        }
        return parent::visit_binary_operation_expression($node);
    }
    public function visit_interpolated_function_expression(Interpolated_Function_Expression $node): Expression
    {
        return $node;
    }
    public function visit_unary_operation_expression(Unary_Operation_Expression $node): Expression
    {
        switch ($node->get_operator()) {
            // `calc()` doesn't support unary operations.
            case Unary_Operator::PLUS:
                return $node->get_operand();
            case Unary_Operator::MINUS:
                return new Binary_Operation_Expression(Binary_Operator::TIMES, new Number_Expression(-1, $node->get_span()), $node->get_operand());
            // Other unary operations don't produce numbers, so keep them as-is to
            // give the user a more useful syntax error after serialization.
            default:
                return parent::visit_unary_operation_expression($node);
        }
    }
}