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

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Exception\Multi_Span_Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Extend\Extend_Util;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Interpolation_Map;
use Scss_Php\Scss_Php\Parser\Selector_Parser;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A selector list.
 *
 * A selector list is composed of {@see ComplexSelector}s. It matches any element
 * that matches any of the component selectors.
 *
 * @internal
 */
final class Selector_List extends Selector
{
    /**
     * The components of this selector.
     *
     * This is never empty.
     *
     * @var non-empty-list<ComplexSelector>
     */
    private readonly array $components;
    /**
     * Parses a selector list from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes. If
     * $allowParent is false, this doesn't allow {@see ParentSelector}s. If
     * $plainCss is true, this parses the selector as plain CSS rather than
     * unresolved Sass.
     *
     * If passed, $interpolationMap maps the text of $contents back to the
     * original location of the selector in the source file.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Interpolation_Map $interpolation_map = null, ?Uri_Interface $url = null, bool $allow_parent = true, bool $plain_css = false): Selector_List
    {
        return (new Selector_Parser($contents, $logger, $url, $allow_parent, $interpolation_map, $plain_css))->parse();
    }
    /**
     * @param list<ComplexSelector> $components
     */
    public function __construct(array $components, File_Span $span)
    {
        if ($components === []) {
            throw new \InvalidArgumentException('components may not be empty.');
        }
        $this->components = $components;
        parent::__construct($span);
    }
    /**
     * @return non-empty-list<ComplexSelector>
     */
    public function get_components(): array
    {
        return $this->components;
    }
    /**
     * Returns a SassScript list that represents this selector.
     *
     * This has the same format as a list returned by `selector-parse()`.
     */
    public function as_sass_list(): Sass_List
    {
        return new Sass_List(array_map(static function (Complex_Selector $complex): \Scss_Php\Scss_Php\Value\Sass_List {
            $result = [];
            foreach ($complex->get_leading_combinators() as $combinator) {
                $result[] = new Sass_String($combinator, false);
            }
            foreach ($complex->get_components() as $component) {
                $result[] = new Sass_String((string) $component->get_selector(), false);
                foreach ($component->get_combinators() as $combinator) {
                    $result[] = new Sass_String($combinator, false);
                }
            }
            return new Sass_List($result, List_Separator::SPACE);
        }, $this->components), List_Separator::COMMA);
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_selector_list($this);
    }
    /**
     * Returns a {@see SelectorList} that matches only elements that are matched by
     * both this and $other.
     *
     * If no such list can be produced, returns `null`.
     */
    public function unify(Selector_List $other): ?Selector_List
    {
        $contents = [];
        foreach ($this->components as $complex1) {
            foreach ($other->components as $complex2) {
                $unified = Extend_Util::unify_complex([$complex1, $complex2], $complex1->get_span());
                if ($unified === null) {
                    continue;
                }
                foreach ($unified as $complex) {
                    $contents[] = $complex;
                }
            }
        }
        return \count($contents) === 0 ? null : new Selector_List($contents, $this->get_span());
    }
    /**
     * Returns a new selector list that represents $this nested within $parent.
     *
     * By default, this replaces {@see ParentSelector}s in $this with $parent. If
     * $preserveParentSelectors is true, this instead preserves those selectors
     * as parent selectors.
     *
     * If $implicitParent is true, this prepends $parent to any
     * {@see ComplexSelector}s in this that don't contain explicit {@see ParentSelector}s,
     * or to _all_ {@see ComplexSelector}s if $preserveParentSelectors is true.
     *
     * The given $parent may be `null`, indicating that this has no parents. If
     * so, this list is returned as-is if it doesn't contain any explicit
     * {@see ParentSelector}s or if $preserveParentSelectors is true. Otherwise, this
     * throws a {@see SassScriptException}.
     */
    public function nest_within(?Selector_List $parent, bool $implicit_parent = true, bool $preserve_parent_selectors = false): Selector_List
    {
        if ($parent === null) {
            if ($preserve_parent_selectors) {
                return $this;
            }
            $parent_selector = $this->accept(new Parent_Selector_Visitor());
            if ($parent_selector === null) {
                return $this;
            }
            throw new Simple_Sass_Exception('Top-level selectors may not contain the parent selector "&".', $parent_selector->get_span());
        }
        return new Selector_List(List_Util::flatten_vertically(array_map(function (Complex_Selector $complex) use ($parent, $implicit_parent, $preserve_parent_selectors) {
            if ($preserve_parent_selectors || !self::contains_parent_selector($complex)) {
                if (!$implicit_parent) {
                    return [$complex];
                }
                return array_map(fn(Complex_Selector $parent_complex): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector => $parent_complex->concatenate($complex, $complex->get_span()), $parent->get_components());
            }
            /** @var list<ComplexSelector> $newComplexes */
            $new_complexes = [];
            foreach ($complex->get_components() as $component) {
                $resolved = self::nest_within_compound($component, $parent);
                if ($resolved === null) {
                    if (\count($new_complexes) === 0) {
                        $new_complexes[] = new Complex_Selector($complex->get_leading_combinators(), [$component], $complex->get_span(), false);
                    } else {
                        $new_complexes = array_map(fn($new_complex): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector => $new_complex->with_additional_component($component, $complex->get_span()), $new_complexes);
                    }
                } elseif (\count($new_complexes) === 0) {
                    if (\count($complex->get_leading_combinators()) === 0) {
                        $new_complexes = $resolved;
                    } else {
                        $new_complexes = array_map(fn(Complex_Selector $resolved_complex): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector => new Complex_Selector(array_merge($complex->get_leading_combinators(), $resolved_complex->get_leading_combinators()), $resolved_complex->get_components(), $complex->get_span(), $resolved_complex->get_line_break()), $resolved);
                    }
                } else {
                    $previous_complexes = $new_complexes;
                    $new_complexes = [];
                    foreach ($previous_complexes as $new_complex) {
                        foreach ($resolved as $resolved_complex) {
                            $new_complexes[] = $new_complex->concatenate($resolved_complex, $new_complex->get_span());
                        }
                    }
                }
            }
            return $new_complexes;
        }, $this->components)), $this->get_span());
    }
    /**
     * Whether this is a superselector of $other.
     *
     * That is, whether this matches every element that $other matches, as well
     * as possibly additional elements.
     */
    public function is_superselector(Selector_List $other): bool
    {
        return Extend_Util::list_is_superselector($this->components, $other->components);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Selector_List && Equatable_Util::list_equals($this->components, $other->components);
    }
    /**
     * Returns a new selector list based on $component with all
     * {@see ParentSelector}s replaced with $parent.
     *
     * Returns `null` if $component doesn't contain any {@see ParentSelector}s.
     *
     * @return list<ComplexSelector>|null
     */
    private static function nest_within_compound(Complex_Selector_Component $component, Selector_List $parent): ?array
    {
        $simples = $component->get_selector()->get_components();
        $contains_selector_pseudo = false;
        foreach ($simples as $simple) {
            if (!$simple instanceof Pseudo_Selector) {
                continue;
            }
            $selector = $simple->get_selector();
            if ($selector !== null && self::contains_parent_selector($selector)) {
                $contains_selector_pseudo = true;
                break;
            }
        }
        if (!$contains_selector_pseudo && !$simples[0] instanceof Parent_Selector) {
            return null;
        }
        if ($contains_selector_pseudo) {
            $resolved_simples = array_map(function (Simple_Selector $simple) use ($parent): Simple_Selector {
                if (!$simple instanceof Pseudo_Selector) {
                    return $simple;
                }
                $selector = $simple->get_selector();
                if ($selector === null) {
                    return $simple;
                }
                if (!self::contains_parent_selector($selector)) {
                    return $simple;
                }
                return $simple->with_selector($selector->nest_within($parent, false));
            }, $simples);
        } else {
            $resolved_simples = $simples;
        }
        $parent_selector = $simples[0];
        if (!$parent_selector instanceof Parent_Selector) {
            return [new Complex_Selector([], [new Complex_Selector_Component(new Compound_Selector($resolved_simples, $component->get_selector()->get_span()), $component->get_combinators(), $component->get_span())], $component->get_span())];
        }
        if (\count($simples) === 1 && $parent_selector->get_suffix() === null) {
            return $parent->with_additional_combinators($component->get_combinators())->get_components();
        }
        return array_map(function (Complex_Selector $complex) use ($parent_selector, $resolved_simples, $component): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector {
            $last_component = $complex->get_last_component();
            if (\count($last_component->get_combinators()) !== 0) {
                throw new Multi_Span_Sass_Exception("Selector \"{$complex}\" can't be used as a parent in a compound selector.", Span_Util::trim_right($last_component->get_span()), 'outer selector', ['parent selector' => $parent_selector->get_span()]);
            }
            $suffix = $parent_selector->get_suffix();
            $last_simples = $last_component->get_selector()->get_components();
            if ($suffix !== null) {
                $last = new Compound_Selector(array_merge(List_Util::except_last($last_simples), [List_Util::last($last_simples)->add_suffix($suffix)], array_slice($resolved_simples, 1)), $component->get_selector()->get_span());
            } else {
                $last = new Compound_Selector(array_merge($last_simples, array_slice($resolved_simples, 1)), $component->get_selector()->get_span());
            }
            $components = List_Util::except_last($complex->get_components());
            $components[] = new Complex_Selector_Component($last, $component->get_combinators(), $component->get_span());
            return new Complex_Selector($complex->get_leading_combinators(), $components, $component->get_span(), $complex->get_line_break());
        }, $parent->get_components());
    }
    /**
     * Returns a copy of `this` with $combinators added to the end of each
     * complex selector in {@see components}].
     *
     * @param list<CssValue<Combinator>> $combinators
     */
    public function with_additional_combinators(array $combinators): Selector_List
    {
        if ($combinators === []) {
            return $this;
        }
        return new Selector_List(array_map(fn(Complex_Selector $complex): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector => $complex->with_additional_combinators($combinators), $this->components), $this->get_span());
    }
    /**
     * Returns whether $selector recursively contains a parent selector.
     */
    private static function contains_parent_selector(Selector $selector): bool
    {
        return $selector->accept(new Parent_Selector_Visitor()) !== null;
    }
}