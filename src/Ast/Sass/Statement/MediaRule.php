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
 * A `@media` rule.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class Media_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly Interpolation $query, array $children, File_Span $span)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    /**
     * The query that determines on which platforms the styles will be in effect.
     *
     * This is only parsed after the interpolation has been resolved.
     */
    public function get_query(): Interpolation
    {
        return $this->query;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_media_rule($this);
    }
    public function __toString(): string
    {
        return '@media ' . $this->query . ' {' . implode(' ', $this->get_children()) . '}';
    }
}