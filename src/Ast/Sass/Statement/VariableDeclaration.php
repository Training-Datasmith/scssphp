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
use Scss_Php\Scss_Php\Ast\Sass\Sass_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A variable declaration.
 *
 * This defines or sets a variable.
 *
 * @internal
 */
final class Variable_Declaration implements Statement, Sass_Declaration
{
    private readonly File_Span $span;
    public function __construct(private readonly string $name, private readonly Expression $expression, File_Span $span, private readonly ?string $namespace = null, private readonly bool $guarded = false, private readonly bool $global = false, private readonly ?Silent_Comment $comment = null)
    {
        $this->span = $span;
        if ($this->namespace !== null && $this->global) {
            throw new \InvalidArgumentException("Other modules' members can't be defined with !global.");
        }
    }
    public function get_namespace(): ?string
    {
        return $this->namespace;
    }
    /**
     * The name of the variable, with underscores converted to hyphens.
     */
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * The variable name as written in the document, without underscores
     * converted to hyphens and including the leading `$`.
     *
     * This isn't particularly efficient, and should only be used for error
     * messages.
     */
    public function get_original_name(): string
    {
        return Util::declaration_name($this->span);
    }
    public function get_comment(): ?Silent_Comment
    {
        return $this->comment;
    }
    public function get_expression(): Expression
    {
        return $this->expression;
    }
    public function is_guarded(): bool
    {
        return $this->guarded;
    }
    public function is_global(): bool
    {
        return $this->global;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function get_name_span(): File_Span
    {
        $span = $this->span;
        if ($this->namespace !== null) {
            $span = Span_Util::without_namespace($span);
        }
        return Span_Util::initial_identifier($span, 1);
    }
    public function get_namespace_span(): ?File_Span
    {
        if ($this->namespace === null) {
            return null;
        }
        return Span_Util::initial_identifier($this->span);
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_variable_declaration($this);
    }
    public function __toString(): string
    {
        $buffer = '';
        if ($this->namespace !== null) {
            $buffer .= $this->namespace . '.';
        }
        return $buffer . "\${$this->name}: {$this->expression};";
    }
}