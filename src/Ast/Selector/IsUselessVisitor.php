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
 * The visitor used to implement {@see Selector::isUseless}.
 *
 * @internal
 */
final class Is_Useless_Visitor extends Any_Selector_Visitor
{
    public function visit_complex_selector(Complex_Selector $complex): bool
    {
        if (\count($complex->get_leading_combinators()) > 1) {
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
        return $pseudo->is_bogus();
    }
}