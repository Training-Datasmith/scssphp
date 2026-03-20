<?php

declare (strict_types=1);
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
 * A {@see SelectorVisitor} whose `visit*` methods default to returning `null`, but
 * which returns the first non-`null` value returned by any method.
 *
 * This can be extended to find the first instance of particular nodes in the
 * AST.
 *
 * @template T
 * @template-implements SelectorVisitor<T|null>
 *
 * @internal
 */
abstract class Selector_Search_Visitor implements Selector_Visitor
{
    public function visit_attribute_selector(Attribute_Selector $attribute)
    {
        return null;
    }
    public function visit_class_selector(Class_Selector $klass)
    {
        return null;
    }
    public function visit_id_selector(Id_Selector $id)
    {
        return null;
    }
    public function visit_parent_selector(Parent_Selector $parent)
    {
        return null;
    }
    public function visit_placeholder_selector(Placeholder_Selector $placeholder)
    {
        return null;
    }
    public function visit_type_selector(Type_Selector $type)
    {
        return null;
    }
    public function visit_universal_selector(Universal_Selector $universal)
    {
        return null;
    }
    public function visit_complex_selector(Complex_Selector $complex)
    {
        return Iterable_Util::search($complex->get_components(), fn(Complex_Selector_Component $component) => $this->visit_compound_selector($component->get_selector()));
    }
    public function visit_compound_selector(Compound_Selector $compound)
    {
        return Iterable_Util::search($compound->get_components(), fn(Simple_Selector $simple) => $simple->accept($this));
    }
    public function visit_pseudo_selector(Pseudo_Selector $pseudo)
    {
        if ($pseudo->get_selector() !== null) {
            return $this->visit_selector_list($pseudo->get_selector());
        }
        return null;
    }
    public function visit_selector_list(Selector_List $list)
    {
        return Iterable_Util::search($list->get_components(), $this->visit_complex_selector(...));
    }
}