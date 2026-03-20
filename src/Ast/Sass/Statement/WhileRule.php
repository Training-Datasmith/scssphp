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
 * A `@while` rule.
 *
 * This repeatedly executes a block of code as long as a statement evaluates to
 * `true`.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class While_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly Expression $condition, array $children, File_Span $span)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    public function get_condition(): Expression
    {
        return $this->condition;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_while_rule($this);
    }
    public function __toString(): string
    {
        return '@while ' . $this->condition . ' {' . implode(' ', $this->get_children()) . '}';
    }
}