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
namespace Scss_Php\Scss_Php\Extend;

use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Ast\Selector\Combinator;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector_Component;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Id_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Qualified_Name;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Extend_Util
{
    /**
     * Pseudo-selectors that can only meaningfully appear in the first component of
     * a complex selector.
     */
    private const ROOTISH_PSEUDO_CLASSES = ['root', 'scope', 'host', 'host-context'];
    /**
     * Returns the contents of a {@see SelectorList} that matches only elements that are
     * matched by every complex selector in $complexes.
     *
     * If no such list can be produced, returns `null`.
     *
     * @param list<ComplexSelector> $complexes
     *
     * @return list<ComplexSelector>|null
     */
    public static function unify_complex(array $complexes, File_Span $span): ?array
    {
        if (\count($complexes) === 1) {
            return $complexes;
        }
        $unified_base = null;
        $leading_combinator = null;
        $trailing_combinator = null;
        foreach ($complexes as $complex) {
            if ($complex->is_useless()) {
                return null;
            }
            if (\count($complex->get_components()) === 1 && \count($complex->get_leading_combinators()) !== 0) {
                $new_leading_combinator = \count($complex->get_leading_combinators()) === 1 ? $complex->get_leading_combinators()[0] : null;
                if ($leading_combinator !== null && !Equatable_Util::equals($new_leading_combinator, $leading_combinator)) {
                    return null;
                }
                $leading_combinator = $new_leading_combinator;
            }
            $base = $complex->get_last_component();
            if (\count($base->get_combinators()) !== 0) {
                $new_trailing_combinator = \count($base->get_combinators()) === 1 ? $base->get_combinators()[0] : null;
                if ($trailing_combinator !== null && $new_trailing_combinator !== $trailing_combinator) {
                    return null;
                }
                $trailing_combinator = $new_trailing_combinator;
            }
            if ($unified_base === null) {
                $unified_base = $base->get_selector()->get_components();
            } else {
                foreach ($base->get_selector()->get_components() as $simple) {
                    $unified_base = $simple->unify($unified_base);
                    if ($unified_base === null) {
                        return null;
                    }
                }
            }
        }
        $without_bases = [];
        $has_line_break = false;
        foreach ($complexes as $complex) {
            if (\count($complex->get_components()) > 1) {
                $without_bases[] = new Complex_Selector($complex->get_leading_combinators(), array_slice($complex->get_components(), 0, \count($complex->get_components()) - 1), $complex->get_span(), $complex->get_line_break());
            }
            if ($complex->get_line_break()) {
                $has_line_break = true;
            }
        }
        \assert($unified_base !== null);
        $base = new Complex_Selector($leading_combinator === null ? [] : [$leading_combinator], [new Complex_Selector_Component(new Compound_Selector($unified_base, $span), $trailing_combinator === null ? [] : [$trailing_combinator], $span)], $span, $has_line_break);
        return self::weave($without_bases === [] ? [$base] : array_merge(List_Util::except_last($without_bases), [List_Util::last($without_bases)->concatenate($base, $span)]), $span);
    }
    /**
     * Returns a {@see CompoundSelector} that matches only elements that are matched by
     * both $compound1 and $compound2.
     *
     * If no such selector can be produced, returns `null`.
     */
    public static function unify_compound(Compound_Selector $compound1, Compound_Selector $compound2): ?Compound_Selector
    {
        $result = $compound2->get_components();
        foreach ($compound1->get_components() as $simple) {
            $unified = $simple->unify($result);
            if ($unified === null) {
                return null;
            }
            $result = $unified;
        }
        return new Compound_Selector($result, $compound1->get_span());
    }
    /**
     * Returns a {@see SimpleSelector} that matches only elements that are matched by
     * both $selector1 and $selector2, which must both be either
     * {@see UniversalSelector}s or {@see TypeSelector}s.
     *
     * If no such selector can be produced, returns `null`.
     */
    public static function unify_universal_and_element(Simple_Selector $selector1, Simple_Selector $selector2): ?Simple_Selector
    {
        [$namespace1, $name1] = self::namespace_and_name($selector1, 'selector1');
        [$namespace2, $name2] = self::namespace_and_name($selector2, 'selector2');
        if ($namespace1 === $namespace2 || $namespace2 === '*') {
            $namespace = $namespace1;
        } elseif ($namespace1 === '*') {
            $namespace = $namespace2;
        } else {
            return null;
        }
        if ($name1 === $name2 || $name2 === null) {
            $name = $name1;
        } elseif ($name1 === null) {
            $name = $name2;
        } else {
            return null;
        }
        if ($name === null) {
            return new Universal_Selector($selector1->get_span(), $namespace);
        }
        return new Type_Selector(new Qualified_Name($name, $namespace), $selector1->get_span());
    }
    /**
     * Returns the namespace and name for $selector, which must be a
     * {@see UniversalSelector} or a {@see TypeSelector}.
     *
     * The $name parameter is used for error reporting.
     *
     * @return array{string|null, string|null} The namespace and the name
     */
    private static function namespace_and_name(Simple_Selector $selector, string $name): array
    {
        if ($selector instanceof Universal_Selector) {
            return [$selector->get_namespace(), null];
        }
        if ($selector instanceof Type_Selector) {
            return [$selector->get_name()->get_namespace(), $selector->get_name()->get_name()];
        }
        throw new \InvalidArgumentException("Argument {$name} must be a UniversalSelector or a TypeSelector.");
    }
    /**
     * Expands "parenthesized selectors" in $complexes.
     *
     * That is, if we have `.A .B {@extend .C}` and `.D .C {...}`, this
     * conceptually expands into `.D .C, .D (.A .B)`, and this function translates
     * `.D (.A .B)` into `.D .A .B, .A .D .B`. For thoroughness, `.A.D .B` would
     * also be required, but including merged selectors results in exponential
     * output for very little gain.
     *
     * The selector `.D (.A .B)` is represented as the list `[.D, .A .B]`.
     *
     * The $span will be used for any new combined selectors.
     *
     * If $forceLineBreak is `true`, this will mark all returned complex selectors
     * as having line breaks.
     *
     * @param list<ComplexSelector> $complexes
     *
     * @return list<ComplexSelector>
     */
    public static function weave(array $complexes, File_Span $span, bool $force_line_break = false): array
    {
        if (\count($complexes) === 1) {
            $complex = $complexes[0];
            if (!$force_line_break || $complex->get_line_break()) {
                return $complexes;
            }
            return [new Complex_Selector($complex->get_leading_combinators(), $complex->get_components(), $complex->get_span(), true)];
        }
        $prefixes = [$complexes[0]];
        foreach (array_slice($complexes, 1) as $complex) {
            if (\count($complex->get_components()) === 1) {
                foreach ($prefixes as $i => $prefix) {
                    $prefixes[$i] = $prefix->concatenate($complex, $span, $force_line_break);
                }
                continue;
            }
            $new_prefixes = [];
            foreach ($prefixes as $prefix) {
                foreach (self::weave_parents($prefix, $complex, $span) ?? [] as $parent_prefix) {
                    $new_prefixes[] = $parent_prefix->with_additional_component(List_Util::last($complex->get_components()), $span, $force_line_break);
                }
            }
            $prefixes = $new_prefixes;
        }
        return $prefixes;
    }
    /**
     * Interweaves $prefix's components with $base's components _other than
     * the last_.
     *
     * Returns all possible orderings of the selectors in the inputs (including
     * using unification) that maintain the relative ordering of the input. For
     * example, given `.foo .bar` and `.baz .bang div`, this would return `.foo
     * .bar .baz .bang div`, `.foo .bar.baz .bang div`, `.foo .baz .bar .bang div`,
     * `.foo .baz .bar.bang div`, `.foo .baz .bang .bar div`, and so on until `.baz
     * .bang .foo .bar div`.
     *
     * Semantically, for selectors `P` and `C`, this returns all selectors `PC_i`
     * such that the union over all `i` of elements matched by `PC_i` is identical
     * to the intersection of all elements matched by `C` and all descendants of
     * elements matched by `P`. Some `PC_i` are elided to reduce the size of the
     * output.
     *
     * The $span will be used for any new combined selectors.
     *
     * Returns `null` if this intersection is empty.
     *
     * @return list<ComplexSelector>|null
     */
    private static function weave_parents(Complex_Selector $prefix, Complex_Selector $base, File_Span $span): ?array
    {
        $leading_combinators = self::merge_leading_combinators($prefix->get_leading_combinators(), $base->get_leading_combinators());
        if ($leading_combinators === null) {
            return null;
        }
        // Make queues of _only_ the parent selectors. The prefix only contains
        // parents, but the complex selector has a target that we don't want to weave
        // in.
        $queue1 = $prefix->get_components();
        $queue2 = List_Util::except_last($base->get_components());
        $final_combinators = self::merge_trailing_combinators($queue1, $queue2, $span);
        if ($final_combinators === null) {
            return null;
        }
        // Make sure all selectors that are required to be at the root are unified
        // with one another.
        $rootish1 = self::first_if_rootish($queue1);
        $rootish2 = self::first_if_rootish($queue2);
        if ($rootish1 !== null && $rootish2 !== null) {
            $rootish = self::unify_compound($rootish1->get_selector(), $rootish2->get_selector());
            if ($rootish === null) {
                return null;
            }
            array_unshift($queue1, new Complex_Selector_Component($rootish, $rootish1->get_combinators(), $rootish1->get_span()));
            array_unshift($queue2, new Complex_Selector_Component($rootish, $rootish2->get_combinators(), $rootish2->get_span()));
        } elseif ($rootish1 !== null || $rootish2 !== null) {
            // If there's only one rootish selector, it should only appear in the first
            // position of the resulting selector. We can ensure that happens by adding
            // it to the beginning of _both_ queues.
            $rootish = $rootish1 ?? $rootish2;
            \assert($rootish !== null);
            array_unshift($queue1, $rootish);
            array_unshift($queue2, $rootish);
        }
        $groups1 = self::group_selectors($queue1);
        $groups2 = self::group_selectors($queue2);
        /** @var list<list<ComplexSelectorComponent>> $lcs */
        $lcs = List_Util::longest_common_subsequence($groups2, $groups1, function ($group1, $group2) use ($span) {
            if (Equatable_Util::list_equals($group1, $group2)) {
                return $group1;
            }
            if (self::complex_is_parent_superselector($group1, $group2)) {
                return $group2;
            }
            if (self::complex_is_parent_superselector($group2, $group1)) {
                return $group1;
            }
            if (!self::must_unify($group1, $group2)) {
                return null;
            }
            $unified = self::unify_complex([new Complex_Selector([], $group1, $span), new Complex_Selector([], $group2, $span)], $span);
            if ($unified === null) {
                return null;
            }
            if (\count($unified) > 1) {
                return null;
            }
            return $unified[0]->get_components();
        });
        $choices = [];
        foreach ($lcs as $group) {
            $new_choice = [];
            /** @var list<list<list<ComplexSelectorComponent>>> $chunks */
            $chunks = self::chunks($groups1, $groups2, fn($sequence): bool => self::complex_is_parent_superselector($sequence[0], $group));
            foreach ($chunks as $chunk) {
                $flattened = [];
                foreach ($chunk as $chunk_group) {
                    $flattened = array_merge($flattened, $chunk_group);
                }
                $new_choice[] = $flattened;
            }
            /** @var list<list<ComplexSelectorComponent>> $groups1 */
            /** @var list<list<ComplexSelectorComponent>> $groups2 */
            $choices[] = $new_choice;
            $choices[] = [$group];
            array_shift($groups1);
            array_shift($groups2);
        }
        $new_choice = [];
        /** @var list<list<list<ComplexSelectorComponent>>> $chunks */
        $chunks = self::chunks($groups1, $groups2, fn($sequence): bool => count($sequence) === 0);
        foreach ($chunks as $chunk) {
            $flattened = [];
            foreach ($chunk as $chunk_group) {
                $flattened = array_merge($flattened, $chunk_group);
            }
            $new_choice[] = $flattened;
        }
        $choices[] = $new_choice;
        foreach ($final_combinators as $final_combinator) {
            $choices[] = $final_combinator;
        }
        $choices = array_filter($choices, fn($choice): bool => $choice !== []);
        $paths = self::paths($choices);
        return array_map(function (array $path) use ($leading_combinators, $prefix, $base, $span): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector {
            $result = [];
            foreach ($path as $group) {
                $result = array_merge($result, $group);
            }
            return new Complex_Selector($leading_combinators, $result, $span, $prefix->get_line_break() || $base->get_line_break());
        }, $paths);
    }
    /**
     * If the first element of $queue has a `:root` selector, removes and returns
     * that element.
     *
     * @param list<ComplexSelectorComponent> $queue
     */
    private static function first_if_rootish(array &$queue): ?Complex_Selector_Component
    {
        if (empty($queue)) {
            return null;
        }
        $first = $queue[0];
        foreach ($first->get_selector()->get_components() as $simple) {
            if ($simple instanceof Pseudo_Selector && $simple->is_class() && \in_array($simple->get_normalized_name(), self::ROOTISH_PSEUDO_CLASSES, true)) {
                array_shift($queue);
                return $first;
            }
        }
        return null;
    }
    /**
     * Returns a leading combinator list that's compatible with both $combinators1
     * and $combinators2.
     *
     * Returns `null` if the combinator lists can't be unified.
     *
     * @param list<CssValue<Combinator>>|null $combinators1
     * @param list<CssValue<Combinator>>|null $combinators2
     *
     * @return list<CssValue<Combinator>>|null
     */
    private static function merge_leading_combinators(?array $combinators1, ?array $combinators2): ?array
    {
        if ($combinators1 === null) {
            return null;
        }
        if ($combinators2 === null) {
            return null;
        }
        if (\count($combinators1) > 1) {
            return null;
        }
        if (\count($combinators2) > 1) {
            return null;
        }
        if (\count($combinators1) === 0) {
            return $combinators2;
        }
        if (\count($combinators2) === 0) {
            return $combinators1;
        }
        return $combinators1 === $combinators2 ? $combinators1 : null;
    }
    /**
     * Extracts trailing {@see ComplexSelectorComponent}s with trailing combinators from
     * $components1 and $components2 and merges them together into a single list.
     *
     *  Each element in the returned list is a set of choices for a particular
     * position in a complex selector. Each choice is the contents of a complex
     * selector, which is to say a list of complex selector components. The union
     * of each path through these choices will match the full set of necessary
     * elements.
     *
     * If there are no combinators to be merged, returns an empty list. If the
     * sequences can't be merged, returns `null`.
     *
     * The $span will be used for any new combined selectors.
     *
     * @param list<ComplexSelectorComponent>             $components1
     * @param list<ComplexSelectorComponent>             $components2
     * @param list<list<list<ComplexSelectorComponent>>> $result
     *
     * @return list<list<list<ComplexSelectorComponent>>>|null
     */
    private static function merge_trailing_combinators(array &$components1, array &$components2, File_Span $span, array $result = []): ?array
    {
        $combinators1 = \count($components1) === 0 ? [] : List_Util::last($components1)->get_combinators();
        $combinators2 = \count($components2) === 0 ? [] : List_Util::last($components2)->get_combinators();
        if (\count($combinators1) === 0 && \count($combinators2) === 0) {
            return $result;
        }
        if (count($combinators1) > 1 || count($combinators2) > 1) {
            return null;
        }
        // This code looks complicated, but it's actually just a bunch of special
        // cases for interactions between different combinators.
        $combinator1 = $combinators1[0] ?? null;
        $combinator2 = $combinators2[0] ?? null;
        if ($combinator1 !== null && $combinator2 !== null) {
            $component1 = array_pop($components1);
            assert($component1 instanceof Complex_Selector_Component);
            $component2 = array_pop($components2);
            assert($component2 instanceof Complex_Selector_Component);
            if ($combinator1->get_value() === Combinator::FOLLOWING_SIBLING && $combinator2->get_value() === Combinator::FOLLOWING_SIBLING) {
                if ($component1->get_selector()->is_superselector($component2->get_selector())) {
                    array_unshift($result, [[$component2]]);
                } elseif ($component2->get_selector()->is_superselector($component1->get_selector())) {
                    array_unshift($result, [[$component1]]);
                } else {
                    $choices = [[$component1, $component2], [$component2, $component1]];
                    $unified = self::unify_compound($component1->get_selector(), $component2->get_selector());
                    if ($unified !== null) {
                        $choices[] = [new Complex_Selector_Component($unified, [$combinator1], $span)];
                    }
                    array_unshift($result, $choices);
                }
            } elseif ($combinator1->get_value() === Combinator::FOLLOWING_SIBLING && $combinator2->get_value() === Combinator::NEXT_SIBLING || $combinator1->get_value() === Combinator::NEXT_SIBLING && $combinator2->get_value() === Combinator::FOLLOWING_SIBLING) {
                $following_sibling_component = $combinator1->get_value() === Combinator::FOLLOWING_SIBLING ? $component1 : $component2;
                $next_sibling_component = $combinator1->get_value() === Combinator::FOLLOWING_SIBLING ? $component2 : $component1;
                if ($following_sibling_component->get_selector()->is_superselector($next_sibling_component->get_selector())) {
                    array_unshift($result, [[$next_sibling_component]]);
                } else {
                    $unified = self::unify_compound($following_sibling_component->get_selector(), $next_sibling_component->get_selector());
                    $choices = [[$following_sibling_component, $next_sibling_component]];
                    if ($unified !== null) {
                        $choices[] = [new Complex_Selector_Component($unified, $next_sibling_component->get_combinators(), $span)];
                    }
                    array_unshift($result, $choices);
                }
            } elseif ($combinator1->get_value() === Combinator::CHILD && ($combinator2->get_value() === Combinator::NEXT_SIBLING || $combinator2->get_value() === Combinator::FOLLOWING_SIBLING)) {
                array_unshift($result, [[$component2]]);
                $components1[] = $component1;
            } elseif ($combinator2->get_value() === Combinator::CHILD && ($combinator1->get_value() === Combinator::NEXT_SIBLING || $combinator1->get_value() === Combinator::FOLLOWING_SIBLING)) {
                array_unshift($result, [[$component1]]);
                $components2[] = $component2;
            } elseif (Equatable_Util::equals($combinator1, $combinator2)) {
                $unified = self::unify_compound($component1->get_selector(), $component2->get_selector());
                if ($unified === null) {
                    return null;
                }
                array_unshift($result, [[new Complex_Selector_Component($unified, [$combinator1], $span)]]);
            } else {
                return null;
            }
            return self::merge_trailing_combinators($components1, $components2, $span, $result);
        }
        if ($combinator1 !== null) {
            $component1 = array_pop($components1);
            \assert($component1 instanceof Complex_Selector_Component);
            if ($combinator1->get_value() === Combinator::CHILD && \count($components2) > 0 && List_Util::last($components2)->get_selector()->is_superselector($component1->get_selector())) {
                array_pop($components2);
            }
            array_unshift($result, [[$component1]]);
            return self::merge_trailing_combinators($components1, $components2, $span, $result);
        }
        $component2 = array_pop($components2);
        \assert($component2 instanceof Complex_Selector_Component);
        assert($combinator2 !== null);
        if ($combinator2->get_value() === Combinator::CHILD && \count($components1) > 0 && List_Util::last($components1)->get_selector()->is_superselector($component2->get_selector())) {
            array_pop($components1);
        }
        array_unshift($result, [[$component2]]);
        return self::merge_trailing_combinators($components1, $components2, $span, $result);
    }
    /**
     * Returns whether $complex1 and $complex2 need to be unified to produce a
     * valid combined selector.
     *
     * This is necessary when both selectors contain the same unique simple
     * selector, such as an ID.
     *
     * @param list<ComplexSelectorComponent> $complex1
     * @param list<ComplexSelectorComponent> $complex2
     */
    private static function must_unify(array $complex1, array $complex2): bool
    {
        $unique_selectors = [];
        foreach ($complex1 as $component) {
            foreach ($component->get_selector()->get_components() as $simple) {
                if (self::is_unique($simple)) {
                    $unique_selectors[] = $simple;
                }
            }
        }
        if (\count($unique_selectors) === 0) {
            return false;
        }
        foreach ($complex2 as $component) {
            foreach ($component->get_selector()->get_components() as $simple) {
                if (self::is_unique($simple) && Equatable_Util::iterable_contains($unique_selectors, $simple)) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Returns whether a {@see CompoundSelector} may contain only one simple selector of
     * the same type as $simple.
     */
    private static function is_unique(Simple_Selector $simple): bool
    {
        return $simple instanceof Id_Selector || $simple instanceof Pseudo_Selector && $simple->is_element();
    }
    /**
     * Returns all orderings of initial subsequences of $queue1 and $queue2.
     *
     * The $done callback is used to determine the extent of the initial
     * subsequences. It's called with each queue until it returns `true`.
     *
     * This destructively removes the initial subsequences of $queue1 and
     * $queue2.
     *
     * For example, given `(A B C | D E)` and `(1 2 | 3 4 5)` (with `|` denoting
     * the boundary of the initial subsequence), this would return `[(A B C 1 2),
     * (1 2 A B C)]`. The queues would then contain `(D E)` and `(3 4 5)`.
     *
     * @template T
     *
     * @param list<T>                 $queue1
     * @param list<T>                 $queue2
     * @param callable(list<T>): bool $done
     *
     * @return list<list<T>>
     *
     * @param-immediately-invoked-callable $done
     */
    private static function chunks(array &$queue1, array &$queue2, callable $done): array
    {
        $chunk1 = [];
        while (!$done($queue1)) {
            $element = array_shift($queue1);
            if ($element === null) {
                throw new \LogicException('Cannot remove an element from an empty queue');
            }
            $chunk1[] = $element;
        }
        $chunk2 = [];
        while (!$done($queue2)) {
            $element = array_shift($queue2);
            if ($element === null) {
                throw new \LogicException('Cannot remove an element from an empty queue');
            }
            $chunk2[] = $element;
        }
        if (empty($chunk1) && empty($chunk2)) {
            return [];
        }
        if (empty($chunk1)) {
            return [$chunk2];
        }
        if (empty($chunk2)) {
            return [$chunk1];
        }
        return [array_merge($chunk1, $chunk2), array_merge($chunk2, $chunk1)];
    }
    /**
     * Returns a list of all possible paths through the given lists.
     *
     * For example, given `[[1, 2], [3, 4], [5]]`, this returns:
     *
     * ```
     * [[1, 3, 5],
     *  [2, 3, 5],
     *  [1, 4, 5],
     *  [2, 4, 5]]
     * ```
     *
     * @template T
     *
     * @param array<list<T>> $choices
     *
     * @return list<list<T>>
     */
    public static function paths(array $choices): array
    {
        return array_reduce($choices, function (array $paths, array $choice): array {
            $new_paths = [];
            foreach ($choice as $option) {
                foreach ($paths as $path) {
                    $path[] = $option;
                    $new_paths[] = $path;
                }
            }
            return $new_paths;
        }, [[]]);
    }
    /**
     * Returns $complex, grouped into the longest possible sub-lists such that
     * {@see ComplexSelectorComponent}s without combinators only appear at the end of
     * sub-lists.
     *
     * For example, `(A B > C D + E ~ G)` is grouped into
     * `[(A) (B > C) (D + E ~ G)]`.
     *
     * @param iterable<ComplexSelectorComponent> $complex
     *
     * @return list<list<ComplexSelectorComponent>>
     */
    private static function group_selectors(iterable $complex): array
    {
        $groups = [];
        $group = [];
        foreach ($complex as $component) {
            $group[] = $component;
            if (\count($component->get_combinators()) === 0) {
                $groups[] = $group;
                $group = [];
            }
        }
        if ($group !== []) {
            $groups[] = $group;
        }
        return $groups;
    }
    /**
     * Returns whether $list1 is a superselector of $list2.
     *
     * That is, whether $list1 matches every element that $list2 matches, as well
     * as possibly additional elements.
     *
     * @param list<ComplexSelector> $list1
     * @param list<ComplexSelector> $list2
     */
    public static function list_is_superselector(array $list1, array $list2): bool
    {
        foreach ($list2 as $complex1) {
            foreach ($list1 as $complex2) {
                if ($complex2->is_superselector($complex1)) {
                    continue 2;
                }
            }
            return false;
        }
        return true;
    }
    /**
     * Like {@see complexIsSuperselector}, but compares $complex1 and $complex2 as
     * though they shared an implicit base {@see SimpleSelector}.
     *
     * For example, `B` is not normally a superselector of `B A`, since it doesn't
     * match elements that match `A`. However, it *is* a parent superselector,
     * since `B X` is a superselector of `B A X`.
     *
     * @param list<ComplexSelectorComponent> $complex1
     * @param list<ComplexSelectorComponent> $complex2
     */
    private static function complex_is_parent_superselector(array $complex1, array $complex2): bool
    {
        if (\count($complex1) > \count($complex2)) {
            return false;
        }
        $bogus_span = Span_Util::bogus_span();
        $base = new Complex_Selector_Component(new Compound_Selector([new Placeholder_Selector('<temp>', $bogus_span)], $bogus_span), [], $bogus_span);
        $complex1[] = $base;
        $complex2[] = $base;
        return self::complex_is_superselector($complex1, $complex2);
    }
    /**
     * Returns whether $complex1 is a superselector of $complex2.
     *
     * That is, whether $complex1 matches every element that $complex2 matches, as well
     * as possibly additional elements.
     *
     * @param list<ComplexSelectorComponent> $complex1
     * @param list<ComplexSelectorComponent> $complex2
     */
    public static function complex_is_superselector(array $complex1, array $complex2): bool
    {
        // Selectors with trailing operators are neither superselectors nor
        // subselectors.
        if (\count(List_Util::last($complex1)->get_combinators()) !== 0) {
            return false;
        }
        if (\count(List_Util::last($complex2)->get_combinators()) !== 0) {
            return false;
        }
        $i1 = 0;
        $i2 = 0;
        $previous_combinator = null;
        while (true) {
            $remaining1 = \count($complex1) - $i1;
            $remaining2 = \count($complex2) - $i2;
            if ($remaining1 === 0 || $remaining2 === 0) {
                return false;
            }
            // More complex selectors are never superselectors of less complex ones.
            if ($remaining1 > $remaining2) {
                return false;
            }
            $component1 = $complex1[$i1];
            if (\count($component1->get_combinators()) > 1) {
                return false;
            }
            if ($remaining1 === 1) {
                if (Iterable_Util::any($complex2, fn(Complex_Selector_Component $parent): bool => \count($parent->get_combinators()) > 1)) {
                    return false;
                }
                return self::compound_is_superselector($component1->get_selector(), List_Util::last($complex2)->get_selector(), $component1->get_selector()->has_complicated_superselector_semantics() ? array_slice($complex2, $i2, -1) : null);
            }
            // Find the first index $endOfSubselector in $complex2 such that
            // `complex2.sublist(i2, endOfSubselector + 1)` is a subselector of
            // `$component1->getSelector()`.
            $end_of_subselector = $i2;
            while (true) {
                $component2 = $complex2[$end_of_subselector];
                if (\count($component2->get_combinators()) > 1) {
                    return false;
                }
                if (self::compound_is_superselector($component1->get_selector(), $component2->get_selector(), $component1->get_selector()->has_complicated_superselector_semantics() ? array_slice($complex2, $i2, $end_of_subselector - $i2) : null)) {
                    break;
                }
                $end_of_subselector++;
                if ($end_of_subselector === \count($complex2) - 1) {
                    // Stop before the superselector would encompass all of $complex2
                    // because we know $complex1 has more than one element, and consuming
                    // all of $complex2 wouldn't leave anything for the rest of $complex1
                    // to match.
                    return false;
                }
            }
            if (!self::compatible_with_previous_combinator($previous_combinator, array_slice($complex2, $i2, $end_of_subselector - $i2))) {
                return false;
            }
            $component2 = $complex2[$end_of_subselector];
            $combinator1 = $component1->get_combinators()[0] ?? null;
            $combinator2 = $component2->get_combinators()[0] ?? null;
            if (!self::is_supercombinator($combinator1, $combinator2)) {
                return false;
            }
            $i1++;
            $i2 = $end_of_subselector + 1;
            $previous_combinator = $combinator1;
            if (\count($complex1) - $i1 === 1) {
                if ($combinator1 !== null && $combinator1->get_value() === Combinator::FOLLOWING_SIBLING) {
                    // The selector `.foo ~ .bar` is only a superselector of selectors that
                    // *exclusively* contain subcombinators of `~`.
                    for ($index = $i2; $index < \count($complex2) - 1; $index++) {
                        $component = $complex2[$index];
                        if (!self::is_supercombinator($combinator1, $component->get_combinators()[0] ?? null)) {
                            return false;
                        }
                    }
                } elseif ($combinator1 !== null) {
                    // `.foo > .bar` and `.foo + bar` aren't superselectors of any selectors
                    // with more than one combinator.
                    if (\count($complex2) - $i2 > 1) {
                        return false;
                    }
                }
            }
        }
    }
    /**
     * @param CssValue<Combinator>|null $previous
     * @param list<ComplexSelectorComponent> $parents
     */
    private static function compatible_with_previous_combinator(?Css_Value $previous, array $parents): bool
    {
        if ($parents === []) {
            return true;
        }
        if ($previous === null) {
            return true;
        }
        // The child and next sibling combinators require that the *immediate*
        // following component be a superselector.
        if ($previous->get_value() !== Combinator::FOLLOWING_SIBLING) {
            return false;
        }
        // The following sibling combinator does allow intermediate components, but
        // only if they're all siblings.
        foreach ($parents as $component) {
            $first_combinator = $component->get_combinators()[0] ?? null;
            $first_combinator_value = $first_combinator?->get_value();
            if ($first_combinator_value !== Combinator::FOLLOWING_SIBLING && $first_combinator_value !== Combinator::NEXT_SIBLING) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns whether $combinator1 is a supercombinator of $combinator2.
     *
     * That is, whether `X $combinator1 Y` is a superselector of `X $combinator2 Y`.
     *
     * @param CssValue<Combinator>|null $combinator1
     * @param CssValue<Combinator>|null $combinator2
     */
    private static function is_supercombinator(?Css_Value $combinator1, ?Css_Value $combinator2): bool
    {
        return Equatable_Util::equals($combinator1, $combinator2) || $combinator1 === null && $combinator2 !== null && $combinator2->get_value() === Combinator::CHILD || $combinator1 !== null && $combinator1->get_value() === Combinator::FOLLOWING_SIBLING && $combinator2 !== null && $combinator2->get_value() === Combinator::NEXT_SIBLING;
    }
    /**
     * Returns whether $compound1 is a superselector of $compound2.
     *
     * That is, whether $compound1 matches every element that $compound2 matches, as well
     * as possibly additional elements.
     *
     * If $parents is passed, it represents the parents of $compound2. This is
     * relevant for pseudo selectors with selector arguments, where we may need to
     * know if the parent selectors in the selector argument match $parents.
     *
     * @param list<ComplexSelectorComponent>|null $parents
     */
    public static function compound_is_superselector(Compound_Selector $compound1, Compound_Selector $compound2, ?array $parents = null): bool
    {
        if (!$compound1->has_complicated_superselector_semantics() && !$compound2->has_complicated_superselector_semantics()) {
            if (\count($compound1->get_components()) > \count($compound2->get_components())) {
                return false;
            }
            return Iterable_Util::every($compound1->get_components(), fn(Simple_Selector $simple1): bool => Iterable_Util::any($compound2->get_components(), $simple1->is_superselector(...)));
        }
        // Pseudo elements effectively change the target of a compound selector rather
        // than narrowing the set of elements to which it applies like other
        // selectors. As such, if either selector has a pseudo element, they both must
        // have the _same_ pseudo element.
        //
        // In addition, order matters when pseudo-elements are involved. The selectors
        // before them must
        $tuple1 = self::find_pseudo_element_indexed($compound1);
        $tuple2 = self::find_pseudo_element_indexed($compound2);
        if ($tuple1 !== null && $tuple2 !== null) {
            return $tuple1[0]->is_superselector($tuple2[0]) && self::compound_components_is_superselector(array_slice($compound1->get_components(), 0, $tuple1[1]), array_slice($compound2->get_components(), 0, $tuple2[1]), $parents) && self::compound_components_is_superselector(array_slice($compound1->get_components(), $tuple1[1] + 1), array_slice($compound2->get_components(), $tuple2[1] + 1), $parents);
        }
        if ($tuple1 !== null || $tuple2 !== null) {
            return false;
        }
        // Every selector in `$compound1->getComponents()` must have a matching selector in
        // `$compound2->getComponents()`.
        foreach ($compound1->get_components() as $simple1) {
            if ($simple1 instanceof Pseudo_Selector && $simple1->get_selector() !== null) {
                if (!self::selector_pseudo_is_superselector($simple1, $compound2, $parents)) {
                    return false;
                }
            } else {
                foreach ($compound2->get_components() as $simple2) {
                    if ($simple1->is_superselector($simple2)) {
                        continue 2;
                    }
                }
                return false;
            }
        }
        return true;
    }
    /**
     * If $compound contains a pseudo-element, returns it and its index in
     * `$compound->getComponents()`.
     *
     * @return array{PseudoSelector, int}|null
     */
    private static function find_pseudo_element_indexed(Compound_Selector $compound): ?array
    {
        foreach ($compound->get_components() as $i => $simple) {
            if ($simple instanceof Pseudo_Selector && $simple->is_element()) {
                return [$simple, $i];
            }
        }
        return null;
    }
    /**
     * Like {@see compoundIsSuperselector} but operates on the underlying lists of
     * simple selectors.
     *
     * @param list<SimpleSelector>                $compound1
     * @param list<SimpleSelector>                $compound2
     * @param list<ComplexSelectorComponent>|null $parents
     */
    private static function compound_components_is_superselector(array $compound1, array $compound2, ?array $parents = null): bool
    {
        if (\count($compound1) === 0) {
            return true;
        }
        $bogus_span = Span_Util::bogus_span();
        if (\count($compound2) === 0) {
            $compound2 = [new Universal_Selector($bogus_span, '*')];
        }
        return self::compound_is_superselector(new Compound_Selector($compound1, $bogus_span), new Compound_Selector($compound2, $bogus_span), $parents);
    }
    /**
     * Returns whether $pseudo1 is a superselector of $compound2.
     *
     * That is, whether $pseudo1 matches every element that $compound2 matches, as well
     * as possibly additional elements.
     *
     * This assumes that $pseudo1's `selector` argument is not `null`.
     *
     * If $parents is passed, it represents the parents of $compound2. This is
     * relevant for pseudo selectors with selector arguments, where we may need to
     * know if the parent selectors in the selector argument match $parents.
     *
     * @param list<ComplexSelectorComponent>|null $parents
     */
    private static function selector_pseudo_is_superselector(Pseudo_Selector $pseudo1, Compound_Selector $compound2, ?array $parents): bool
    {
        $selector1 = $pseudo1->get_selector();
        if ($selector1 === null) {
            throw new \InvalidArgumentException("Selector {$pseudo1} must have a selector argument.");
        }
        switch ($pseudo1->get_normalized_name()) {
            case 'is':
            case 'matches':
            case 'any':
            case 'where':
                $selectors = self::selector_pseudo_args($compound2, $pseudo1->get_name());
                foreach ($selectors as $selector2) {
                    if ($selector1->is_superselector($selector2)) {
                        return true;
                    }
                }
                $component_with_parents = $parents;
                $component_with_parents[] = new Complex_Selector_Component($compound2, [], $compound2->get_span());
                foreach ($selector1->get_components() as $complex1) {
                    if (\count($complex1->get_leading_combinators()) === 0 && self::complex_is_superselector($complex1->get_components(), $component_with_parents)) {
                        return true;
                    }
                }
                return false;
            case 'has':
            case 'host':
            case 'host-context':
                $selectors = self::selector_pseudo_args($compound2, $pseudo1->get_name());
                foreach ($selectors as $selector2) {
                    if ($selector1->is_superselector($selector2)) {
                        return true;
                    }
                }
                return false;
            case 'slotted':
                $selectors = self::selector_pseudo_args($compound2, $pseudo1->get_name(), false);
                foreach ($selectors as $selector2) {
                    if ($selector1->is_superselector($selector2)) {
                        return true;
                    }
                }
                return false;
            case 'not':
                foreach ($selector1->get_components() as $complex) {
                    if ($complex->is_bogus()) {
                        return false;
                    }
                    foreach ($compound2->get_components() as $simple2) {
                        if ($simple2 instanceof Type_Selector) {
                            foreach ($complex->get_last_component()->get_selector()->get_components() as $simple1) {
                                if ($simple1 instanceof Type_Selector && !$simple1->equals($simple2)) {
                                    continue 3;
                                }
                            }
                        } elseif ($simple2 instanceof Id_Selector) {
                            foreach ($complex->get_last_component()->get_selector()->get_components() as $simple1) {
                                if ($simple1 instanceof Id_Selector && !$simple1->equals($simple2)) {
                                    continue 3;
                                }
                            }
                        } elseif ($simple2 instanceof Pseudo_Selector && $simple2->get_name() === $pseudo1->get_name()) {
                            $selector2 = $simple2->get_selector();
                            if ($selector2 === null) {
                                continue;
                            }
                            if (self::list_is_superselector($selector2->get_components(), [$complex])) {
                                continue 2;
                            }
                        }
                    }
                    return false;
                }
                return true;
            case 'current':
                $selectors = self::selector_pseudo_args($compound2, $pseudo1->get_name());
                foreach ($selectors as $selector2) {
                    if ($selector1->equals($selector2)) {
                        return true;
                    }
                }
                return false;
            case 'nth-child':
            case 'nth-last-child':
                foreach ($compound2->get_components() as $pseudo2) {
                    if (!$pseudo2 instanceof Pseudo_Selector) {
                        continue;
                    }
                    if ($pseudo2->get_name() !== $pseudo1->get_name()) {
                        continue;
                    }
                    if ($pseudo2->get_argument() !== $pseudo1->get_argument()) {
                        continue;
                    }
                    $selector2 = $pseudo2->get_selector();
                    if ($selector2 === null) {
                        continue;
                    }
                    if ($selector1->is_superselector($selector2)) {
                        return true;
                    }
                }
                return false;
            default:
                throw new \LogicException('unreachache');
        }
    }
    /**
     * Returns all the selector arguments of pseudo selectors in $compound with
     * the given $name.
     *
     * @return SelectorList[]
     */
    private static function selector_pseudo_args(Compound_Selector $compound, string $name, bool $is_class = true): array
    {
        $selectors = [];
        foreach ($compound->get_components() as $simple) {
            if (!$simple instanceof Pseudo_Selector) {
                continue;
            }
            if ($simple->is_class() !== $is_class) {
                continue;
            }
            if ($simple->get_name() !== $name) {
                continue;
            }
            if ($simple->get_selector() === null) {
                continue;
            }
            $selectors[] = $simple->get_selector();
        }
        return $selectors;
    }
}