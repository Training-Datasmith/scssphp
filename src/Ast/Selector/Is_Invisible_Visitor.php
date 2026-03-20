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
namespace Scss_Php\Scss_Php\Ast\Selector;

use Scss_Php\Scss_Php\Visitor\Any_Selector_Visitor;
/**
 * The visitor used to implement {@see Selector::isInvisible}.
 *
 * @internal
 */
final class Is_Invisible_Visitor extends Any_Selector_Visitor
{
    public function __construct(
        /**
         * Whether to consider selectors with bogus combinators invisible.
         */
        private readonly bool $include_bogus
    )
    {
    }
    public function visit_selector_list(Selector_List $list): bool
    {
        foreach ($list->get_components() as $complex) {
            if (!$this->visit_complex_selector($complex)) {
                return false;
            }
        }
        return true;
    }
    public function visit_complex_selector(Complex_Selector $complex): bool
    {
        if (parent::visit_complex_selector($complex)) {
            return true;
        }
        return $this->include_bogus && $complex->is_bogus_other_than_leading_combinator();
    }
    public function visit_placeholder_selector(Placeholder_Selector $placeholder): bool
    {
        return true;
    }
    public function visit_pseudo_selector(Pseudo_Selector $pseudo): bool
    {
        $selector = $pseudo->get_selector();
        if ($selector === null) {
            return false;
        }
        // We don't consider `:not(%foo)` to be invisible because, semantically, it
        // means "doesn't match this selector that matches nothing", so it's
        // equivalent to *. If the entire compound selector is composed of `:not`s
        // with invisible lists, the serializer emits it as `*`.
        return $pseudo->get_name() === 'not' ? $this->include_bogus && $selector->is_bogus() : $selector->accept($this);
    }
}