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
/**
 * An interface for visitors that traverse SassScript expressions.
 *
 * @internal
 *
 * @template T
 */
interface Expression_Visitor
{
    /**
     * @return T
     */
    public function visit_binary_operation_expression(Binary_Operation_Expression $node);
    /**
     * @return T
     */
    public function visit_boolean_expression(Boolean_Expression $node);
    /**
     * @return T
     */
    public function visit_color_expression(Color_Expression $node);
    /**
     * @return T
     */
    public function visit_interpolated_function_expression(Interpolated_Function_Expression $node);
    /**
     * @return T
     */
    public function visit_function_expression(Function_Expression $node);
    /**
     * @return T
     */
    public function visit_if_expression(If_Expression $node);
    /**
     * @return T
     */
    public function visit_list_expression(List_Expression $node);
    /**
     * @return T
     */
    public function visit_map_expression(Map_Expression $node);
    /**
     * @return T
     */
    public function visit_null_expression(Null_Expression $node);
    /**
     * @return T
     */
    public function visit_number_expression(Number_Expression $node);
    /**
     * @return T
     */
    public function visit_parenthesized_expression(Parenthesized_Expression $node);
    /**
     * @return T
     */
    public function visit_selector_expression(Selector_Expression $node);
    /**
     * @return T
     */
    public function visit_string_expression(String_Expression $node);
    /**
     * @return T
     */
    public function visit_supports_expression(Supports_Expression $node);
    /**
     * @return T
     */
    public function visit_unary_operation_expression(Unary_Operation_Expression $node);
    /**
     * @return T
     */
    public function visit_value_expression(Value_Expression $node);
    /**
     * @return T
     */
    public function visit_variable_expression(Variable_Expression $node);
}