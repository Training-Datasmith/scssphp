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
namespace Scss_Php\Scss_Php\Ast\Sass\Expression;

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Sass_Reference;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A Sass variable.
 *
 * @internal
 */
final class Variable_Expression implements Expression, Sass_Reference
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The name of this variable, with underscores converted to hyphens.
         */
        private readonly string $name,
        File_Span $span,
        /**
         * The namespace of the variable being referenced, or `null` if it's
         * referenced without a namespace.
         */
        private readonly ?string $namespace = null
    )
    {
        $this->span = $span;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_namespace(): ?string
    {
        return $this->namespace;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function get_name_span(): File_Span
    {
        if ($this->namespace === null) {
            return $this->span;
        }
        return Span_Util::without_namespace($this->span);
    }
    public function get_namespace_span(): ?File_Span
    {
        if ($this->namespace === null) {
            return null;
        }
        return Span_Util::initial_identifier($this->span);
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_variable_expression($this);
    }
    public function __toString(): string
    {
        return (string) $this->span->get_text();
    }
}