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

use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A `@at-root` rule.
 *
 * This moves it contents "up" the tree through parent nodes.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class At_Root_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(array $children, File_Span $span, private readonly ?Interpolation $query = null)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    /**
     * The query specifying which statements this should move its contents through.
     */
    public function get_query(): ?Interpolation
    {
        return $this->query;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_at_root_rule($this);
    }
    public function __toString(): string
    {
        $buffer = '@at-root ';
        if ($this->query !== null) {
            $buffer .= $this->query . ' ';
        }
        return $buffer . '{' . implode(' ', $this->get_children()) . '}';
    }
}