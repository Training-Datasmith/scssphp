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
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * An `@each` rule.
 *
 * This iterates over values in a list or map.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class Each_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param list<string> $variables
     * @param Statement[] $children
     */
    public function __construct(private readonly array $variables, private readonly Expression $list, array $children, File_Span $span)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    /**
     * @return list<string>
     */
    public function get_variables(): array
    {
        return $this->variables;
    }
    public function get_list(): Expression
    {
        return $this->list;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_each_rule($this);
    }
    public function __toString(): string
    {
        return '@each ' . implode(', ', array_map(fn(string $variable): string => '$' . $variable, $this->variables)) . ' in ' . $this->list . ' {' . implode(' ', $this->get_children()) . '}';
    }
}