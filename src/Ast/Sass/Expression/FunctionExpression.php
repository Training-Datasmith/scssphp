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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Callable_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Sass_Reference;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A function invocation.
 *
 * This may be a plain CSS function or a Sass function,  but may not include
 * interpolation.
 *
 * @internal
 */
final class Function_Expression implements Expression, Callable_Invocation, Sass_Reference
{
    /**
     * The name of the function being invoked, with underscores converted to
     * hyphens.
     *
     * If this function is a plain CSS function, use {@see $originalName} instead.
     */
    private readonly string $name;
    private readonly File_Span $span;
    public function __construct(
        /**
         * The name of the function being invoked, with underscores left as-is.
         */
        private readonly string $original_name,
        /**
         * The arguments to pass to the function.
         */
        private readonly Argument_Invocation $arguments,
        File_Span $span,
        /**
         * The namespace of the function being invoked, or `null` if it's invoked
         * without a namespace.
         */
        private readonly ?string $namespace = null
    )
    {
        $this->span = $span;
        $this->name = str_replace('_', '-', $this->original_name);
    }
    public function get_original_name(): string
    {
        return $this->original_name;
    }
    /**
     * The name of the function being invoked, with underscores converted to
     * hyphens.
     *
     * If this function is a plain CSS function, use {@see getOriginalName} instead.
     */
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_arguments(): Argument_Invocation
    {
        return $this->arguments;
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
            return Span_Util::initial_identifier($this->span);
        }
        return Span_Util::initial_identifier(Span_Util::without_namespace($this->span));
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
        return $visitor->visit_function_expression($this);
    }
    public function __toString(): string
    {
        $buffer = '';
        if ($this->namespace !== null) {
            $buffer .= $this->namespace . '.';
        }
        return $buffer . ($this->original_name . $this->arguments);
    }
}