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

namespace ScssPhp\ScssPhp\Ast\Sass\SupportsCondition;

use ScssPhp\ScssPhp\Ast\Sass\Expression;
use ScssPhp\ScssPhp\Ast\Sass\SupportsCondition;
use SourceSpan\FileSpan;

/**
 * An interpolated condition.
 *
 * @internal
 */
final class SupportsInterpolation implements SupportsCondition
{
    private readonly FileSpan $span;

    public function __construct(/**
     * The expression in the interpolation.
     */
    private readonly Expression $expression, FileSpan $span)
    {
        $this->span = $span;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function __toString(): string
    {
        return '#{' . $this->expression . '}';
    }
}
