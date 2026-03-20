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

use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
/**
 * A statement in a Sass syntax tree.
 *
 * @internal
 */
interface Statement extends Sass_Node
{
    /**
     * @template T
     * @param StatementVisitor<T> $visitor
     * @return T
     */
    public function accept(Statement_Visitor $visitor);
}