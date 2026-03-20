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

use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * An `@extend` rule.
 *
 * This gives one selector all the styling of another.
 *
 * @internal
 */
final class Extend_Rule implements Statement
{
    private readonly File_Span $span;
    public function __construct(private readonly Interpolation $selector, File_Span $span, private readonly bool $optional = false)
    {
        $this->span = $span;
    }
    public function get_selector(): Interpolation
    {
        return $this->selector;
    }
    /**
     * Whether this is an optional extension.
     *
     * If an extension isn't optional, it will emit an error if it doesn't match
     * any selectors.
     */
    public function is_optional(): bool
    {
        return $this->optional;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_extend_rule($this);
    }
    public function __toString(): string
    {
        return '@extend ' . $this->selector . ($this->optional ? ' !optional' : '') . ';';
    }
}