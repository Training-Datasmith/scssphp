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
namespace Scss_Php\Scss_Php\Visitor;

use Scss_Php\Scss_Php\Ast\Selector\Attribute_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Class_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector_Component;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Id_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Parent_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
use Scss_Php\Scss_Php\Util\Iterable_Util;
/**
 * A visitor that visits each selector in a Sass selector AST and returns
 * `true` if any of the individual methods return `true`.
 *
 * Each method returns `false` by default.
 *
 * @template-implements SelectorVisitor<bool>
 * @internal
 */
abstract class Any_Selector_Visitor implements Selector_Visitor
{
    public function visit_complex_selector(Complex_Selector $complex): bool
    {
        return Iterable_Util::any($complex->get_components(), fn(Complex_Selector_Component $component): bool => $this->visit_compound_selector($component->get_selector()));
    }
    public function visit_compound_selector(Compound_Selector $compound): bool
    {
        return Iterable_Util::any($compound->get_components(), fn(Simple_Selector $simple) => $simple->accept($this));
    }
    public function visit_pseudo_selector(Pseudo_Selector $pseudo): bool
    {
        $selector = $pseudo->get_selector();
        return $selector === null ? false : $selector->accept($this);
    }
    public function visit_selector_list(Selector_List $list): bool
    {
        return Iterable_Util::any($list->get_components(), $this->visit_complex_selector(...));
    }
    public function visit_attribute_selector(Attribute_Selector $attribute): bool
    {
        return false;
    }
    public function visit_class_selector(Class_Selector $klass): bool
    {
        return false;
    }
    public function visit_id_selector(Id_Selector $id): bool
    {
        return false;
    }
    public function visit_parent_selector(Parent_Selector $parent): bool
    {
        return false;
    }
    public function visit_placeholder_selector(Placeholder_Selector $placeholder): bool
    {
        return false;
    }
    public function visit_type_selector(Type_Selector $type): bool
    {
        return false;
    }
    public function visit_universal_selector(Universal_Selector $universal): bool
    {
        return false;
    }
}