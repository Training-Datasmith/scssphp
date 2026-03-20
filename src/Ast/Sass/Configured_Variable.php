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

use Scss_Php\Scss_Php\Util\Span_Util;
use Source_Span\File_Span;
/**
 * A variable configured by a `with` clause in a `@use` or `@forward` rule.
 *
 * @internal
 */
final class Configured_Variable implements Sass_Node, Sass_Declaration
{
    private readonly File_Span $span;
    public function __construct(private readonly string $name, private readonly Expression $expression, File_Span $span, private readonly bool $guarded = false)
    {
        $this->span = $span;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_expression(): Expression
    {
        return $this->expression;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function is_guarded(): bool
    {
        return $this->guarded;
    }
    public function get_name_span(): File_Span
    {
        return Span_Util::initial_identifier($this->span, 1);
    }
    public function __toString(): string
    {
        return '$' . $this->name . ': ' . $this->expression . ($this->guarded ? ' !default' : '');
    }
}