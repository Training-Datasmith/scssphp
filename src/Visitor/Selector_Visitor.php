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
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Id_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Parent_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
/**
 * An interface for visitors that traverse selectors.
 *
 * @internal
 *
 * @template T
 */
interface Selector_Visitor
{
    /**
     * @return T
     */
    public function visit_attribute_selector(Attribute_Selector $attribute);
    /**
     * @return T
     */
    public function visit_class_selector(Class_Selector $klass);
    /**
     * @return T
     */
    public function visit_complex_selector(Complex_Selector $complex);
    /**
     * @return T
     */
    public function visit_compound_selector(Compound_Selector $compound);
    /**
     * @return T
     */
    public function visit_id_selector(Id_Selector $id);
    /**
     * @return T
     */
    public function visit_parent_selector(Parent_Selector $parent);
    /**
     * @return T
     */
    public function visit_placeholder_selector(Placeholder_Selector $placeholder);
    /**
     * @return T
     */
    public function visit_pseudo_selector(Pseudo_Selector $pseudo);
    /**
     * @return T
     */
    public function visit_selector_list(Selector_List $list);
    /**
     * @return T
     */
    public function visit_type_selector(Type_Selector $type);
    /**
     * @return T
     */
    public function visit_universal_selector(Universal_Selector $universal);
}