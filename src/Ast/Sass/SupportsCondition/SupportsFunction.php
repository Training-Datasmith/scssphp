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
namespace Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;

use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Source_Span\File_Span;
/**
 * A function-syntax condition.
 *
 * @internal
 */
final class Supports_Function implements Supports_Condition
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The name of the function.
         */
        private readonly Interpolation $name,
        /**
         * The arguments of the function.
         */
        private readonly Interpolation $arguments,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_name(): Interpolation
    {
        return $this->name;
    }
    public function get_arguments(): Interpolation
    {
        return $this->arguments;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        return "{$this->name}({$this->arguments})";
    }
}