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
namespace Scss_Php\Scss_Php\Ast\Sass\Statement;

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
/**
 * An `@if` or `@else if` clause in an `@if` rule.
 *
 * @internal
 */
final class If_Clause extends If_Rule_Clause
{
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly Expression $expression, array $children)
    {
        parent::__construct($children);
    }
    public function get_expression(): Expression
    {
        return $this->expression;
    }
}