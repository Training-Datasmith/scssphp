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
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
/**
 * @internal
 */
final class Ast_Util
{
    /**
     * Converts $expression to an equivalent `calc()`.
     *
     * This assumes that $expression already returns a number. It's intended for
     * use in end-user messaging, and may not produce directly evaluable
     * expressions.
     */
    public static function expression_to_calc(Expression $expression): Function_Expression
    {
        return new Function_Expression('calc', new Argument_Invocation([$expression->accept(new Make_Expression_Calculation_Safe())], [], $expression->get_span()), $expression->get_span());
    }
}