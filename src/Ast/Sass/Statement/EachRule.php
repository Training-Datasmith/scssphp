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
use ScssPhp\ScssPhp\Visitor\StatementVisitor;
use SourceSpan\FileSpan;

/**
 * An `@each` rule.
 *
 * This iterates over values in a list or map.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class EachRule extends ParentStatement
{
    private readonly FileSpan $span;

    /**
     * @param list<string> $variables
     * @param Statement[] $children
     */
    public function __construct(private readonly array $variables, private readonly Expression $list, array $children, FileSpan $span)
    {
        $this->span = $span;
        parent::__construct($children);
    }

    /**
     * @return list<string>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getList(): Expression
    {
        return $this->list;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function accept(StatementVisitor $visitor)
    {
        return $visitor->visitEachRule($this);
    }

    public function __toString(): string
    {
        return '@each ' . implode(', ', array_map(fn(string $variable): string => '$' . $variable, $this->variables)) . ' in ' . $this->list . ' {' . implode(' ', $this->getChildren()) . '}';
    }
}
