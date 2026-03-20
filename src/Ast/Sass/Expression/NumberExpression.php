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
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A number literal.
 *
 * @internal
 */
final class Number_Expression implements Expression
{
    private readonly File_Span $span;
    public function __construct(private readonly float $value, File_Span $span, private readonly ?string $unit = null)
    {
        $this->span = $span;
    }
    public function get_value(): float
    {
        return $this->value;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function get_unit(): ?string
    {
        return $this->unit;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_number_expression($this);
    }
    public function __toString(): string
    {
        return (string) Sass_Number::create($this->value, $this->unit);
    }
}