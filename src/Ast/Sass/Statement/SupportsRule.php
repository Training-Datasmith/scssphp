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

use ScssPhp\ScssPhp\Ast\Sass\Statement;
use ScssPhp\ScssPhp\Ast\Sass\SupportsCondition;
use ScssPhp\ScssPhp\Visitor\StatementVisitor;
use SourceSpan\FileSpan;

/**
 * A `@supports` rule.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class SupportsRule extends ParentStatement
{
    private readonly FileSpan $span;

    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly SupportsCondition $condition, array $children, FileSpan $span)
    {
        $this->span = $span;
        parent::__construct($children);
    }

    public function getCondition(): SupportsCondition
    {
        return $this->condition;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function accept(StatementVisitor $visitor)
    {
        return $visitor->visitSupportsRule($this);
    }

    public function __toString(): string
    {
        return '@supports ' . $this->condition . ' {' . implode(' ', $this->getChildren()) . '}';
    }
}
