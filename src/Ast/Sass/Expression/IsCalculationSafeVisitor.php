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
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
/**
 * @template-implements ExpressionVisitor<bool>
 *
 * @internal
 */
final class Is_Calculation_Safe_Visitor implements Expression_Visitor
{
    public function visit_binary_operation_expression(Binary_Operation_Expression $node): bool
    {
        return \in_array($node->get_operator(), [Binary_Operator::TIMES, Binary_Operator::DIVIDED_BY, Binary_Operator::PLUS, Binary_Operator::MINUS], true) && ($node->get_left()->accept($this) || $node->get_right()->accept($this));
    }
    public function visit_boolean_expression(Boolean_Expression $node): bool
    {
        return false;
    }
    public function visit_color_expression(Color_Expression $node): bool
    {
        return false;
    }
    public function visit_function_expression(Function_Expression $node): bool
    {
        return true;
    }
    public function visit_interpolated_function_expression(Interpolated_Function_Expression $node): bool
    {
        return true;
    }
    public function visit_if_expression(If_Expression $node): bool
    {
        return true;
    }
    public function visit_list_expression(List_Expression $node): bool
    {
        return $node->get_separator() === List_Separator::SPACE && !$node->has_brackets() && \count($node->get_contents()) > 1 && Iterable_Util::every($node->get_contents(), fn(Expression $expression) => $expression->accept($this));
    }
    public function visit_map_expression(Map_Expression $node): bool
    {
        return false;
    }
    public function visit_null_expression(Null_Expression $node): bool
    {
        return false;
    }
    public function visit_number_expression(Number_Expression $node): bool
    {
        return true;
    }
    public function visit_parenthesized_expression(Parenthesized_Expression $node): bool
    {
        return $node->get_expression()->accept($this);
    }
    public function visit_selector_expression(Selector_Expression $node): bool
    {
        return false;
    }
    public function visit_string_expression(String_Expression $node): bool
    {
        if ($node->has_quotes()) {
            return false;
        }
        /**
         * Exclude non-identifier constructs that are parsed as {@see StringExpression}s.
         * We could just check if they parse as valid identifiers, but this is
         * cheaper.
         */
        $text = $node->get_text()->get_initial_plain();
        // !important
        return !str_starts_with($text, '!') && !str_starts_with($text, '#') && ($text[1] ?? null) !== '+' && ($text[3] ?? null) !== '(';
    }
    public function visit_supports_expression(Supports_Expression $node): bool
    {
        return false;
    }
    public function visit_unary_operation_expression(Unary_Operation_Expression $node): bool
    {
        return false;
    }
    public function visit_value_expression(Value_Expression $node): bool
    {
        return false;
    }
    public function visit_variable_expression(Variable_Expression $node): bool
    {
        return true;
    }
}