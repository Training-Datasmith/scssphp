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
namespace Scss_Php\Scss_Php\Ast\Css;

use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
use Source_Span\File_Span;
/**
 * A modifiable version of {@see CssSupportsRule} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Supports_Rule extends Modifiable_Css_Parent_Node implements Css_Supports_Rule
{
    private readonly File_Span $span;
    /**
     * @param CssValue<string> $condition
     */
    public function __construct(private readonly Css_Value $condition, File_Span $span)
    {
        parent::__construct();
        $this->span = $span;
    }
    public function get_condition(): Css_Value
    {
        return $this->condition;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_supports_rule($this);
    }
    public function equals_ignoring_children(Modifiable_Css_Node $other): bool
    {
        return $other instanceof Modifiable_Css_Supports_Rule && Equatable_Util::equals($this->condition, $other->condition);
    }
    public function copy_without_children(): Modifiable_Css_Supports_Rule
    {
        return new Modifiable_Css_Supports_Rule($this->condition, $this->span);
    }
}