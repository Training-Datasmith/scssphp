<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Ast\Sass\Statement;

use ScssPhp\ScssPhp\Ast\Sass\Expression;
use ScssPhp\ScssPhp\Ast\Sass\Statement;

/**
 * An `@if` or `@else if` clause in an `@if` rule.
 *
 * @internal
 */
final class IfClause extends IfRuleClause
{
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly Expression $expression, array $children)
    {
        parent::__construct($children);
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }
}
