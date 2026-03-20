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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Callable_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Sass_Reference;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A mixin invocation.
 *
 * @internal
 */
final class Include_Rule implements Statement, Callable_Invocation, Sass_Reference
{
    private readonly string $name;
    private readonly File_Span $span;
    public function __construct(private readonly string $original_name, private readonly Argument_Invocation $arguments, File_Span $span, private readonly ?string $namespace = null, private readonly ?Content_Block $content = null)
    {
        $this->name = str_replace('_', '-', $this->original_name);
        $this->span = $span;
    }
    public function get_namespace(): ?string
    {
        return $this->namespace;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * The original name of the mixin being invoked, without underscores
     * converted to hyphens.
     */
    public function get_original_name(): string
    {
        return $this->original_name;
    }
    public function get_arguments(): Argument_Invocation
    {
        return $this->arguments;
    }
    public function get_content(): ?Content_Block
    {
        return $this->content;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function get_span_without_content(): File_Span
    {
        if ($this->content === null) {
            return $this->span;
        }
        return Span_Util::trim($this->span->get_file()->span($this->span->get_start()->get_offset(), $this->arguments->get_span()->get_end()->get_offset()));
    }
    public function get_name_span(): File_Span
    {
        $start_span = $this->span->get_text()[0] === '+' ? Span_Util::trim_left($this->span->subspan(1)) : Span_Util::without_initial_at_rule($this->span);
        if ($this->namespace !== null) {
            $start_span = Span_Util::without_namespace($start_span);
        }
        return Span_Util::initial_identifier($start_span);
    }
    public function get_namespace_span(): ?File_Span
    {
        if ($this->namespace === null) {
            return null;
        }
        $start_span = $this->span->get_text()[0] === '+' ? Span_Util::trim_left($this->span->subspan(1)) : Span_Util::without_initial_at_rule($this->span);
        return Span_Util::initial_identifier($start_span);
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_include_rule($this);
    }
    public function __toString(): string
    {
        $buffer = '@include ';
        if ($this->namespace !== null) {
            $buffer .= $this->namespace . '.';
        }
        $buffer .= $this->name;
        if (!$this->arguments->is_empty()) {
            $buffer .= "({$this->arguments})";
        }
        $buffer .= $this->content === null ? ';' : ' ' . $this->content;
        return $buffer;
    }
}