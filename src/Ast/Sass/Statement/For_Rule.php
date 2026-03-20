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
 * A `@for` rule.
 *
 * This iterates a set number of times.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class For_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly string $variable, private readonly Expression $from, private readonly Expression $to, array $children, File_Span $span, private readonly bool $exclusive = false)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    public function get_variable(): string
    {
        return $this->variable;
    }
    public function get_from(): Expression
    {
        return $this->from;
    }
    public function get_to(): Expression
    {
        return $this->to;
    }
    /**
     * Whether {@see getTo} is exclusive.
     */
    public function is_exclusive(): bool
    {
        return $this->exclusive;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_for_rule($this);
    }
    public function __toString(): string
    {
        return '@for $' . $this->variable . ' from ' . $this->from . ($this->exclusive ? ' to ' : ' through ') . $this->to . '{' . implode(' ', $this->get_children()) . '}';
    }
}