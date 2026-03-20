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
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Extend\Extend_Util;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Selector_Parser;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A complex selector.
 *
 * A complex selector is composed of {@see CompoundSelector}s separated by
 * {@see Combinator}s. It selects elements based on their parent selectors.
 *
 * @internal
 */
final class Complex_Selector extends Selector
{
    /**
     * This selector's leading combinators.
     *
     * If this is empty, that indicates that it has no leading combinator. If
     * it's more than one element, that means it's invalid CSS; however, we still
     * support this for backwards-compatibility purposes.
     *
     * @var list<CssValue<Combinator>>
     */
    private readonly array $leading_combinators;
    /**
     * The components of this selector.
     *
     * This is only empty if {@see $leadingCombinators} is not empty.
     *
     * Descendant combinators aren't explicitly represented here. If two
     * {@see CompoundSelector}s are adjacent to one another, there's an implicit
     * descendant combinator between them.
     *
     * It's possible for multiple {@see Combinator}s to be adjacent to one another.
     * This isn't valid CSS, but Sass supports it for CSS hack purposes.
     *
     * @var list<ComplexSelectorComponent>
     */
    private readonly array $components;
    private ?int $specificity = null;
    /**
     * @param list<CssValue<Combinator>>     $leadingCombinators
     * @param list<ComplexSelectorComponent> $components
     */
    public function __construct(
        array $leading_combinators,
        array $components,
        File_Span $span,
        /**
         * Whether a line break should be emitted *before* this selector.
         */
        private readonly bool $line_break = false
    )
    {
        if ($leading_combinators === [] && $components === []) {
            throw new \InvalidArgumentException('leadingCombinators and components may not both be empty.');
        }
        $this->leading_combinators = $leading_combinators;
        $this->components = $components;
        parent::__construct($span);
    }
    /**
     * Parses a complex selector from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes.
     * $allowParent controls whether a {@see ParentSelector} is allowed in this
     * selector.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null, bool $allow_parent = true): Complex_Selector
    {
        return (new Selector_Parser($contents, $logger, $url, $allow_parent))->parse_complex_selector();
    }
    /**
     * @return list<CssValue<Combinator>>
     */
    public function get_leading_combinators(): array
    {
        return $this->leading_combinators;
    }
    /**
     * @return list<ComplexSelectorComponent>
     */
    public function get_components(): array
    {
        return $this->components;
    }
    /**
     * If this compound selector is composed of a single compound selector with
     * no combinators, returns it.
     *
     * Otherwise, returns null.
     */
    public function get_single_compound(): ?Compound_Selector
    {
        if (\count($this->leading_combinators) === 0 && \count($this->components) === 1 && \count($this->components[0]->get_combinators()) === 0) {
            return $this->components[0]->get_selector();
        }
        return null;
    }
    public function get_last_component(): Complex_Selector_Component
    {
        if (\count($this->components) === 0) {
            throw new \OutOfBoundsException('Cannot get the last component of an empty list.');
        }
        return $this->components[\count($this->components) - 1];
    }
    public function get_line_break(): bool
    {
        return $this->line_break;
    }
    /**
     * This selector's specificity.
     *
     * Specificity is represented in base 1000. The spec says this should be
     * "sufficiently high"; it's extremely unlikely that any single selector
     * sequence will contain 1000 simple selectors.
     */
    public function get_specificity(): int
    {
        if ($this->specificity === null) {
            $specificity = 0;
            foreach ($this->components as $component) {
                $specificity += $component->get_selector()->get_specificity();
            }
            $this->specificity = $specificity;
        }
        return $this->specificity;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_complex_selector($this);
    }
    /**
     * Whether this is a superselector of $other.
     *
     * That is, whether this matches every element that $other matches, as well
     * as possibly additional elements.
     */
    public function is_superselector(Complex_Selector $other): bool
    {
        return \count($this->leading_combinators) === 0 && \count($other->leading_combinators) === 0 && Extend_Util::complex_is_superselector($this->components, $other->components);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Complex_Selector && Equatable_Util::list_equals($this->leading_combinators, $other->leading_combinators) && Equatable_Util::list_equals($this->components, $other->components);
    }
    /**
     * Returns a copy of `$this` with $combinators added to the end of the final
     * component in {@see components}.
     *
     * If $forceLineBreak is `true`, this will mark the new complex selector as
     * having a line break.
     *
     * @param list<CssValue<Combinator>> $combinators
     */
    public function with_additional_combinators(array $combinators, bool $force_line_break = false): Complex_Selector
    {
        if ($combinators === []) {
            return $this;
        }
        if ($this->components === []) {
            return new Complex_Selector(array_merge($this->leading_combinators, $combinators), [], $this->get_span(), $this->line_break || $force_line_break);
        }
        return new Complex_Selector($this->leading_combinators, array_merge(List_Util::except_last($this->components), [List_Util::last($this->components)->with_additional_combinators($combinators)]), $this->get_span(), $this->line_break || $force_line_break);
    }
    /**
     * Returns a copy of `$this` with an additional $component added to the end.
     *
     * If $forceLineBreak is `true`, this will mark the new complex selector as
     * having a line break.
     */
    public function with_additional_component(Complex_Selector_Component $component, File_Span $span, bool $force_line_break = false): Complex_Selector
    {
        return new Complex_Selector($this->leading_combinators, array_merge($this->components, [$component]), $span, $this->line_break || $force_line_break);
    }
    /**
     * Returns a copy of `this` with $child's combinators added to the end.
     *
     * If $child has {@see leadingCombinators}, they're appended to `this`'s last
     * combinator. This does _not_ resolve parent selectors.
     *
     * If $forceLineBreak is `true`, this will mark the new complex selector as
     * having a line break.
     */
    public function concatenate(Complex_Selector $child, File_Span $span, bool $force_line_break = false): Complex_Selector
    {
        if (\count($child->leading_combinators) === 0) {
            return new Complex_Selector($this->leading_combinators, array_merge($this->components, $child->components), $span, $this->line_break || $child->line_break || $force_line_break);
        }
        if (\count($this->components) === 0) {
            return new Complex_Selector(array_merge($this->leading_combinators, $child->leading_combinators), $child->components, $span, $this->line_break || $child->line_break || $force_line_break);
        }
        return new Complex_Selector($this->leading_combinators, array_merge(List_Util::except_last($this->components), [List_Util::last($this->components)->with_additional_combinators($child->leading_combinators)], $child->components), $span, $this->line_break || $child->line_break || $force_line_break);
    }
}