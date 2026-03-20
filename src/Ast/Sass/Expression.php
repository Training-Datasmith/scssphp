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
namespace Scss_Php\Scss_Php\Ast\Sass;

use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
/**
 * A SassScript expression in a Sass syntax tree.
 *
 * @internal
 */
interface Expression extends Sass_Node
{
    /**
     * @template T
     * @param ExpressionVisitor<T> $visitor
     * @return T
     */
    public function accept(Expression_Visitor $visitor);
}