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
 * The visitor used to implement {@see Selector::isBogus}.
 *
 * @internal
 */
final class Is_Bogus_Visitor extends Any_Selector_Visitor
{
    public function __construct(
        /**
         * Whether to consider selectors with leading combinators as bogus.
         */
        private readonly bool $include_leading_combinator
    )
    {
    }
    public function visit_complex_selector(Complex_Selector $complex): bool
    {
        if (\count($complex->get_components()) === 0) {
            return \count($complex->get_leading_combinators()) > 0;
        }
        if (\count($complex->get_leading_combinators()) > ($this->include_leading_combinator ? 0 : 1) || count($complex->get_last_component()->get_combinators()) !== 0) {
            return true;
        }
        foreach ($complex->get_components() as $component) {
            if (\count($component->get_combinators()) > 1 || $component->get_selector()->accept($this)) {
                return true;
            }
        }
        return false;
    }
    public function visit_pseudo_selector(Pseudo_Selector $pseudo): bool
    {
        $selector = $pseudo->get_selector();
        if ($selector === null) {
            return false;
        }
        // The CSS spec specifically allows leading combinators in `:has()`.
        return $pseudo->get_name() === 'has' ? $selector->is_bogus_other_than_leading_combinator() : $selector->is_bogus();
    }
}