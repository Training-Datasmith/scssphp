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

use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Util\Equatable;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Source_Span\File_Span;
/**
 * A component of a {@see ComplexSelector}.
 *
 * This a {@see CompoundSelector} with one or more trailing {@see Combinator}s.
 *
 * @internal
 */
final class Complex_Selector_Component implements Equatable, \Stringable
{
    private readonly File_Span $span;
    /**
     * @param list<CssValue<Combinator>> $combinators
     */
    public function __construct(
        /**
         * This component's compound selector.
         */
        private readonly Compound_Selector $selector,
        /**
         * This selector's combinators.
         *
         * If this is empty, that indicates that it has an implicit descendent
         * combinator. If it's more than one element, that means it's invalid CSS;
         * however, we still support this for backwards-compatibility purposes.
         */
        private readonly array $combinators,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_selector(): Compound_Selector
    {
        return $this->selector;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    /**
     * @return list<CssValue<Combinator>>
     */
    public function get_combinators(): array
    {
        return $this->combinators;
    }
    public function equals(object $other): bool
    {
        return $other instanceof Complex_Selector_Component && $this->selector->equals($other->selector) && Equatable_Util::list_equals($this->combinators, $other->combinators);
    }
    /**
     * Returns a copy of $this with $combinators added to the end of
     * `$this->combinators`.
     *
     * @param list<CssValue<Combinator>> $combinators
     */
    public function with_additional_combinators(array $combinators): Complex_Selector_Component
    {
        if ($combinators === []) {
            return $this;
        }
        return new Complex_Selector_Component($this->selector, array_merge($this->combinators, $combinators), $this->span);
    }
    public function __toString(): string
    {
        return $this->selector . implode('', array_map(fn(\Scss_Php\Scss_Php\Ast\Css\Css_Value $combinator): string => ' ' . $combinator, $this->combinators));
    }
}