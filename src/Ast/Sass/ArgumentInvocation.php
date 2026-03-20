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
namespace Scss_Php\Scss_Php\Ast\Sass;

use Scss_Php\Scss_Php\Ast\Sass\Expression\List_Expression;
use Scss_Php\Scss_Php\Value\List_Separator;
use Source_Span\File_Span;
/**
 * A set of arguments passed in to a function or mixin.
 *
 * @internal
 */
final class Argument_Invocation implements Sass_Node
{
    private readonly ?Expression $rest;
    private readonly ?Expression $keyword_rest;
    private readonly File_Span $span;
    /**
     * @param list<Expression>          $positional
     * @param array<string, Expression> $named
     */
    public function __construct(private readonly array $positional, private readonly array $named, File_Span $span, ?Expression $rest = null, ?Expression $keyword_rest = null)
    {
        assert($keyword_rest === null || $rest !== null);
        $this->rest = $rest;
        $this->keyword_rest = $keyword_rest;
        $this->span = $span;
    }
    public static function create_empty(File_Span $span): Argument_Invocation
    {
        return new self([], [], $span);
    }
    public function is_empty(): bool
    {
        return \count($this->positional) === 0 && \count($this->named) === 0 && $this->rest === null;
    }
    /**
     * @return list<Expression>
     */
    public function get_positional(): array
    {
        return $this->positional;
    }
    /**
     * @return array<string, Expression>
     */
    public function get_named(): array
    {
        return $this->named;
    }
    public function get_rest(): ?Expression
    {
        return $this->rest;
    }
    public function get_keyword_rest(): ?Expression
    {
        return $this->keyword_rest;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        $parts = [];
        foreach ($this->positional as $argument) {
            $parts[] = $this->parenthesize_argument($argument);
        }
        foreach ($this->named as $name => $arg) {
            $parts[] = "\${$name}: {$this->parenthesize_argument($arg)}";
        }
        if ($this->rest !== null) {
            $parts[] = "{$this->parenthesize_argument($this->rest)}...";
        }
        if ($this->keyword_rest !== null) {
            $parts[] = "{$this->parenthesize_argument($this->keyword_rest)}...";
        }
        return '(' . implode(', ', $parts) . ')';
    }
    private function parenthesize_argument(Expression $argument): string
    {
        if ($argument instanceof List_Expression && $argument->get_separator() === List_Separator::COMMA && !$argument->has_brackets() && \count($argument->get_contents()) > 1) {
            return "({$argument})";
        }
        return (string) $argument;
    }
}