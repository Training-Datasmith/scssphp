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
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A declaration (that is, a `name: value` pair).
 *
 * @extends ParentStatement<Statement[]|null>
 *
 * @internal
 */
final class Declaration extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[]|null $children
     */
    private function __construct(
        private readonly Interpolation $name,
        /**
         * The value of this declaration.
         *
         * If {@see getChildren} is `null`, this is never `null`. Otherwise, it may or may
         * not be `null`.
         */
        private readonly ?Expression $value,
        File_Span $span,
        ?array $children = null
    )
    {
        $this->span = $span;
        parent::__construct($children);
    }
    public static function create(Interpolation $name, Expression $value, File_Span $span): self
    {
        return new self($name, $value, $span);
    }
    /**
     * @param Statement[] $children
     */
    public static function nested(Interpolation $name, array $children, File_Span $span, ?Expression $value = null): self
    {
        return new self($name, $value, $span, $children);
    }
    public function get_name(): Interpolation
    {
        return $this->name;
    }
    public function get_value(): ?Expression
    {
        return $this->value;
    }
    /**
     * Returns whether this is a CSS Custom Property declaration.
     *
     * Note that this can return `false` for declarations that will ultimately be
     * serialized as custom properties if they aren't *parsed as* custom
     * properties, such as `#{--foo}: ...`.
     *
     * If this is `true`, then `value` will be a {@see StringExpression}.
     */
    public function is_custom_property(): bool
    {
        return str_starts_with($this->name->get_initial_plain(), '--');
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_declaration($this);
    }
    public function __toString(): string
    {
        $buffer = $this->name . ':';
        if ($this->value !== null) {
            if (!$this->is_custom_property()) {
                $buffer .= ' ';
            }
            $buffer .= $this->value;
        }
        $children = $this->get_children();
        if ($children === null) {
            return $buffer . ';';
        }
        return $buffer . '{' . implode(' ', $children) . '}';
    }
}