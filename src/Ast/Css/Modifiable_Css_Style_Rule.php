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

use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Util\Box;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
use Source_Span\File_Span;
/**
 * A modifiable version of {@see CssStyleRule} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Style_Rule extends Modifiable_Css_Parent_Node implements Css_Style_Rule
{
    private readonly Selector_List $original_selector;
    private readonly File_Span $span;
    /**
     * @param Box<SelectorList> $selector
     */
    public function __construct(
        /**
         * A reference to the modifiable selector list provided by the extension
         * store, which may update it over time as new extensions are applied.
         */
        private readonly Box $selector,
        File_Span $span,
        ?Selector_List $original_selector = null,
        private readonly bool $from_plain_css = false
    )
    {
        parent::__construct();
        $this->original_selector = $original_selector ?? $this->selector->get_value();
        $this->span = $span;
    }
    public function get_selector(): Selector_List
    {
        return $this->selector->get_value();
    }
    public function get_original_selector(): Selector_List
    {
        return $this->original_selector;
    }
    public function is_from_plain_css(): bool
    {
        return $this->from_plain_css;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_style_rule($this);
    }
    public function equals_ignoring_children(Modifiable_Css_Node $other): bool
    {
        return $other instanceof Modifiable_Css_Style_Rule && Equatable_Util::equals($this->selector, $other->selector);
    }
    public function copy_without_children(): Modifiable_Css_Style_Rule
    {
        return new Modifiable_Css_Style_Rule($this->selector, $this->span, $this->original_selector);
    }
}