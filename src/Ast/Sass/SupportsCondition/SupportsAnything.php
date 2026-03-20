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
 * A supports condition that represents the forwards-compatible
 * `<general-enclosed>` production.
 *
 * @internal
 */
final class Supports_Anything implements Supports_Condition
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The contents of the condition.
         */
        private readonly Interpolation $contents,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_contents(): Interpolation
    {
        return $this->contents;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        return "({$this->contents})";
    }
}