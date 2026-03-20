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

use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * An `@if` rule.
 *
 * This conditionally executes a block of code.
 *
 * @internal
 */
final class If_Rule implements Statement
{
    private readonly File_Span $span;
    /**
     * @param list<IfClause> $clauses
     */
    public function __construct(private readonly array $clauses, File_Span $span, private readonly ?Else_Clause $last_clause = null)
    {
        $this->span = $span;
    }
    /**
     * The `@if` and `@else if` clauses.
     *
     * The first clause whose expression evaluates to `true` will have its
     * statements executed. If no expression evaluates to `true`, `lastClause`
     * will be executed if it's not `null`.
     *
     * @return list<IfClause>
     */
    public function get_clauses(): array
    {
        return $this->clauses;
    }
    /**
     * The final, unconditional `@else` clause.
     *
     * This is `null` if there is no unconditional `@else`.
     */
    public function get_last_clause(): ?Else_Clause
    {
        return $this->last_clause;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_if_rule($this);
    }
    public function __toString(): string
    {
        $parts = [];
        foreach ($this->clauses as $index => $clause) {
            $parts[] = ($index === 0 ? '@if ' : '@else if ') . $clause->get_expression() . '{' . implode(' ', $clause->get_children()) . '}';
        }
        if ($this->last_clause !== null) {
            $parts[] = $this->last_clause;
        }
        return implode(' ', $parts);
    }
}