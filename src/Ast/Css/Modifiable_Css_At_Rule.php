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
 * A modifiable version of {@see CssAtRule} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_At_Rule extends Modifiable_Css_Parent_Node implements Css_At_Rule
{
    private readonly File_Span $span;
    /**
     * @param CssValue<string> $name
     * @param CssValue<string>|null $value
     */
    public function __construct(private readonly Css_Value $name, File_Span $span, private readonly bool $childless = false, private readonly ?Css_Value $value = null)
    {
        parent::__construct();
        $this->span = $span;
    }
    public function get_name(): Css_Value
    {
        return $this->name;
    }
    public function get_value(): ?Css_Value
    {
        return $this->value;
    }
    public function is_childless(): bool
    {
        return $this->childless;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_at_rule($this);
    }
    public function equals_ignoring_children(Modifiable_Css_Node $other): bool
    {
        return $other instanceof Modifiable_Css_At_Rule && Equatable_Util::equals($this->name, $other->name) && Equatable_Util::equals($this->value, $other->value) && $this->childless === $other->childless;
    }
    public function copy_without_children(): Modifiable_Css_At_Rule
    {
        return new Modifiable_Css_At_Rule($this->name, $this->span, $this->childless, $this->value);
    }
    public function add_child(Modifiable_Css_Node $child): void
    {
        if ($this->childless) {
            throw new \LogicException('Cannot add a child in a childless at-rule.');
        }
        parent::add_child($child);
    }
}