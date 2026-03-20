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
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * An expression-level `@supports` condition.
 *
 * This appears only in the modifiers that come after a plain-CSS `@import`. It
 * doesn't include the function name wrapping the condition.
 *
 * @internal
 */
final class Supports_Expression implements Expression
{
    public function __construct(private readonly Supports_Condition $condition)
    {
    }
    public function get_condition(): Supports_Condition
    {
        return $this->condition;
    }
    public function get_span(): File_Span
    {
        return $this->condition->get_span();
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_supports_expression($this);
    }
    public function __toString(): string
    {
        return (string) $this->condition;
    }
}