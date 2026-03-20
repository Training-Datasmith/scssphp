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
namespace Scss_Php\Scss_Php\Function;

use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector_Component;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Parent_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
use Scss_Php\Scss_Php\Evaluation\Evaluation_Context;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Extend\Concrete_Extension_Store;
use Scss_Php\Scss_Php\Util\Array_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
/**
 * @internal
 */
final class Selector_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function nest(array $arguments): Value
    {
        $selectors = $arguments[0]->as_list();
        if (\count($selectors) === 0) {
            throw new Sass_Script_Exception('$selectors: At least one selector must be passed.');
        }
        $first = true;
        return Array_Util::reduce(array_map(function (Value $selector) use (&$first): \Scss_Php\Scss_Php\Ast\Selector\Selector_List {
            $result = $selector->assert_selector(allowParent: !$first);
            $first = false;
            return $result;
        }, $selectors), fn(Selector_List $parent, Selector_List $child): \Scss_Php\Scss_Php\Ast\Selector\Selector_List => $child->nest_within($parent))->as_sass_list();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function append(array $arguments): Value
    {
        $selectors = $arguments[0]->as_list();
        if (\count($selectors) === 0) {
            throw new Sass_Script_Exception('$selectors: At least one selector must be passed.');
        }
        $span = Evaluation_Context::get_current()->get_current_callable_span();
        return Array_Util::reduce(array_map(fn(Value $selector): \Scss_Php\Scss_Php\Ast\Selector\Selector_List => $selector->assert_selector(), $selectors), fn(Selector_List $parent, Selector_List $child) => (new Selector_List(array_map(function (Complex_Selector $complex) use ($span, $parent): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector {
            if (\count($complex->get_leading_combinators()) > 0) {
                throw new Sass_Script_Exception("Can't append {$complex} to {$parent}.");
            }
            $component = $complex->get_components()[0];
            $rest = array_slice($complex->get_components(), 1);
            $new_compound = self::prepend_parent($component->get_selector());
            if ($new_compound === null) {
                throw new Sass_Script_Exception("Can't append {$complex} to {$parent}.");
            }
            return new Complex_Selector([], [new Complex_Selector_Component($new_compound, $component->get_combinators(), $span), ...$rest], $span);
        }, $child->get_components()), $span))->nest_within($parent))->as_sass_list();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function extend(array $arguments): \Scss_Php\Scss_Php\Value\Sass_List
    {
        $selector = $arguments[0]->assert_selector('selector');
        $selector->assert_not_bogus('selector');
        $target = $arguments[1]->assert_selector('extendee');
        $target->assert_not_bogus('extendee');
        $source = $arguments[2]->assert_selector('extender');
        $source->assert_not_bogus('extender');
        return Concrete_Extension_Store::extend($selector, $source, $target, Evaluation_Context::get_current()->get_current_callable_span())->as_sass_list();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function replace(array $arguments): \Scss_Php\Scss_Php\Value\Sass_List
    {
        $selector = $arguments[0]->assert_selector('selector');
        $selector->assert_not_bogus('selector');
        $target = $arguments[1]->assert_selector('original');
        $target->assert_not_bogus('original');
        $source = $arguments[2]->assert_selector('replacement');
        $source->assert_not_bogus('replacement');
        return Concrete_Extension_Store::replace($selector, $source, $target, Evaluation_Context::get_current()->get_current_callable_span())->as_sass_list();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function unify(array $arguments): Value
    {
        $selector1 = $arguments[0]->assert_selector('selector1');
        $selector1->assert_not_bogus('selector1');
        $selector2 = $arguments[1]->assert_selector('selector2');
        $selector2->assert_not_bogus('selector2');
        return $selector1->unify($selector2)?->as_sass_list() ?? Sass_Null::create();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function is_superselector(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        $selector1 = $arguments[0]->assert_selector('super');
        $selector1->assert_not_bogus('super');
        $selector2 = $arguments[1]->assert_selector('sub');
        $selector2->assert_not_bogus('sub');
        return Sass_Boolean::create($selector1->is_superselector($selector2));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function simple_selectors(array $arguments): \Scss_Php\Scss_Php\Value\Sass_List
    {
        $selector = $arguments[0]->assert_compound_selector('selector');
        return new Sass_List(array_map(fn(Simple_Selector $simple): \Scss_Php\Scss_Php\Value\Sass_String => new Sass_String((string) $simple, false), $selector->get_components()), List_Separator::COMMA);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function parse(array $arguments): Value
    {
        return $arguments[0]->assert_selector('selector')->as_sass_list();
    }
    /**
     * Adds a {@see ParentSelector} to the beginning of $compound, or returns `null` if
     * that wouldn't produce a valid selector.
     */
    private static function prepend_parent(Compound_Selector $compound): ?Compound_Selector
    {
        $span = Evaluation_Context::get_current()->get_current_callable_span();
        $first_component = $compound->get_components()[0];
        if ($first_component instanceof Universal_Selector) {
            return null;
        }
        if ($first_component instanceof Type_Selector && $first_component->get_name()->get_namespace() !== null) {
            return null;
        }
        if ($first_component instanceof Type_Selector) {
            return new Compound_Selector([new Parent_Selector($span, $first_component->get_name()->get_name()), ...array_slice($compound->get_components(), 1)], $span);
        }
        return new Compound_Selector([new Parent_Selector($span), ...$compound->get_components()], $span);
    }
}