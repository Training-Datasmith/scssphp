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
 * An unknown at-rule.
 *
 * @extends ParentStatement<Statement[]|null>
 *
 * @internal
 */
final class At_Rule extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[]|null $children
     */
    public function __construct(private readonly Interpolation $name, File_Span $span, private readonly ?Interpolation $value = null, ?array $children = null)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    public function get_name(): Interpolation
    {
        return $this->name;
    }
    public function get_value(): ?Interpolation
    {
        return $this->value;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_at_rule($this);
    }
    public function __toString(): string
    {
        $buffer = '@' . $this->name;
        if ($this->value !== null) {
            $buffer .= ' ' . $this->value;
        }
        $children = $this->get_children();
        if ($children === null) {
            return $buffer . ';';
        }
        return $buffer . '{' . implode(' ', $children) . '}';
    }
}