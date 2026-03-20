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
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * An interpolated function invocation.
 *
 * This is always a plain CSS function.
 *
 * @internal
 */
final class Interpolated_Function_Expression implements Expression, Callable_Invocation
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The name of the function being invoked.
         */
        private readonly Interpolation $name,
        /**
         * The arguments to pass to the function.
         */
        private readonly Argument_Invocation $arguments,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_name(): Interpolation
    {
        return $this->name;
    }
    public function get_arguments(): Argument_Invocation
    {
        return $this->arguments;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_interpolated_function_expression($this);
    }
    public function __toString(): string
    {
        return $this->name . $this->arguments;
    }
}