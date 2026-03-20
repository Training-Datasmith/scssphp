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
namespace Scss_Php\Scss_Php\Visitor;

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Boolean_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Color_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\If_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Interpolated_Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\List_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Map_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Null_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Number_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Parenthesized_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Selector_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Supports_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Value_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Variable_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Negation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Operation;
/**
 * A visitor that recursively traverses each expression in a SassScript AST and
 * replaces its contents with the values returned by nested recursion.
 *
 * In addition to the methods from {@see ExpressionVisitor}, this has more general
 * protected methods that can be overridden to add behavior for a wide variety
 * of AST nodes:
 *
 * * {@see visitArgumentInvocation}
 * * {@see visitSupportsCondition}
 * * {@see visitInterpolation}
 *
 * @template-implements ExpressionVisitor<Expression>
 *
 * @internal
 */
abstract class Replace_Expression_Visitor implements Expression_Visitor
{
    public function visit_binary_operation_expression(Binary_Operation_Expression $node): Expression
    {
        return new Binary_Operation_Expression($node->get_operator(), $node->get_left()->accept($this), $node->get_right()->accept($this));
    }
    public function visit_boolean_expression(Boolean_Expression $node): Expression
    {
        return $node;
    }
    public function visit_color_expression(Color_Expression $node): Expression
    {
        return $node;
    }
    public function visit_function_expression(Function_Expression $node): Expression
    {
        return new Function_Expression($node->get_original_name(), $this->visit_argument_invocation($node->get_arguments()), $node->get_span(), $node->get_namespace());
    }
    public function visit_interpolated_function_expression(Interpolated_Function_Expression $node): Expression
    {
        return new Interpolated_Function_Expression($this->visit_interpolation($node->get_name()), $this->visit_argument_invocation($node->get_arguments()), $node->get_span());
    }
    public function visit_if_expression(If_Expression $node): Expression
    {
        return new If_Expression($this->visit_argument_invocation($node->get_arguments()), $node->get_span());
    }
    public function visit_list_expression(List_Expression $node): Expression
    {
        return new List_Expression(array_map(fn(Expression $item) => $item->accept($this), $node->get_contents()), $node->get_separator(), $node->get_span(), $node->has_brackets());
    }
    public function visit_map_expression(Map_Expression $node): Expression
    {
        return new Map_Expression(array_map(fn(array $pair): array => [$pair[0]->accept($this), $pair[1]->accept($this)], $node->get_pairs()), $node->get_span());
    }
    public function visit_null_expression(Null_Expression $node): Expression
    {
        return $node;
    }
    public function visit_number_expression(Number_Expression $node): Expression
    {
        return $node;
    }
    public function visit_parenthesized_expression(Parenthesized_Expression $node): Expression
    {
        return new Parenthesized_Expression($node->get_expression()->accept($this), $node->get_span());
    }
    public function visit_selector_expression(Selector_Expression $node): Expression
    {
        return $node;
    }
    public function visit_string_expression(String_Expression $node): Expression
    {
        return new String_Expression($this->visit_interpolation($node->get_text()), $node->has_quotes());
    }
    public function visit_supports_expression(Supports_Expression $node): Expression
    {
        return new Supports_Expression($this->visit_supports_condition($node->get_condition()));
    }
    public function visit_unary_operation_expression(Unary_Operation_Expression $node): Expression
    {
        return new Unary_Operation_Expression($node->get_operator(), $node->get_operand()->accept($this), $node->get_span());
    }
    public function visit_value_expression(Value_Expression $node): Expression
    {
        return $node;
    }
    public function visit_variable_expression(Variable_Expression $node): Expression
    {
        return $node;
    }
    /**
     * Replaces each expression in an invocation.
     *
     * The default implementation of the visit methods calls this to replace any
     * argument invocation in an expression.
     */
    protected function visit_argument_invocation(Argument_Invocation $invocation): Argument_Invocation
    {
        return new Argument_Invocation(array_map(fn(Expression $expression) => $expression->accept($this), $invocation->get_positional()), array_map(fn(Expression $expression) => $expression->accept($this), $invocation->get_named()), $invocation->get_span(), $invocation->get_rest()?->accept($this), $invocation->get_keyword_rest()?->accept($this));
    }
    /**
     * Replaces each expression in $condition.
     *
     * The default implementation of the visit methods call this to visit any
     * {@see SupportsCondition} they encounter.
     */
    protected function visit_supports_condition(Supports_Condition $condition): Supports_Condition
    {
        if ($condition instanceof Supports_Operation) {
            return new Supports_Operation($this->visit_supports_condition($condition->get_left()), $this->visit_supports_condition($condition->get_right()), $condition->get_operator(), $condition->get_span());
        }
        if ($condition instanceof Supports_Negation) {
            return new Supports_Negation($this->visit_supports_condition($condition->get_condition()), $condition->get_span());
        }
        if ($condition instanceof Supports_Interpolation) {
            return new Supports_Interpolation($condition->get_expression()->accept($this), $condition->get_span());
        }
        if ($condition instanceof Supports_Declaration) {
            return new Supports_Declaration($condition->get_name()->accept($this), $condition->get_value()->accept($this), $condition->get_span());
        }
        throw new \UnexpectedValueException('BUG: Unknown SupportsCondition ' . $condition::class);
    }
    protected function visit_interpolation(Interpolation $interpolation): Interpolation
    {
        return new Interpolation(array_map(fn(\Scss_Php\Scss_Php\Ast\Sass\Expression|string $node) => $node instanceof Expression ? $node->accept($this) : $node, $interpolation->get_contents()), $interpolation->get_span());
    }
}