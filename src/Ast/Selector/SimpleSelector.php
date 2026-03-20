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
use Scss_Php\Scss_Php\Exception\Multi_Span_Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Selector_Parser;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
/**
 * An abstract superclass for simple selectors.
 *
 * @internal
 */
abstract class Simple_Selector extends Selector
{
    /**
     * Names of pseudo-classes that take selectors as arguments, and that are
     * subselectors of their arguments.
     *
     * For example, `.foo` is a superselector of `:matches(.foo)`.
     */
    private const SUBSELECTOR_PSEUDOS = ['is', 'matches', 'where', 'any', 'nth-child', 'nth-last-child'];
    /**
     * Parses a simple selector from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes.
     * $allowParent controls whether a {@see ParentSelector} is allowed in this
     * selector.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null, bool $allow_parent = true): Simple_Selector
    {
        return (new Selector_Parser($contents, $logger, $url, $allow_parent))->parse_simple_selector();
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
        return 1000;
    }
    /**
     * Whether this requires complex non-local reasoning to determine whether
     * it's a super- or sub-selector.
     *
     * This includes both pseudo-elements and pseudo-selectors that take
     * selectors as arguments.
     *
     * @internal
     */
    public function has_complicated_superselector_semantics(): bool
    {
        return false;
    }
    /**
     * Returns a new {@see SimpleSelector} based on $this, as though it had been
     * written with $suffix at the end.
     *
     * Assumes $suffix is a valid identifier suffix. If this wouldn't produce a
     * valid SimpleSelector, throws an exception.
     *
     * @throws SassException
     */
    public function add_suffix(string $suffix): Simple_Selector
    {
        throw new Multi_Span_Sass_Exception("Invalid parent selector \"{$this}\"", $this->get_span(), 'outer selector', []);
    }
    /**
     * Returns the components of a {@see CompoundSelector} that matches only elements
     * matched by both this and $compound.
     *
     * By default, this just returns a copy of $compound with this selector
     * added to the end, or returns the original array if this selector already
     * exists in it.
     *
     * Returns `null` if unification is impossible—for example, if there are
     * multiple ID selectors.
     *
     * @param list<SimpleSelector> $compound
     *
     * @return list<SimpleSelector>|null
     */
    public function unify(array $compound): ?array
    {
        if (\count($compound) === 1) {
            $other = $compound[0];
            if ($other instanceof Universal_Selector || $other instanceof Pseudo_Selector && ($other->is_host() || $other->is_host_context())) {
                return $other->unify([$this]);
            }
        }
        if (Equatable_Util::iterable_contains($compound, $this)) {
            return $compound;
        }
        $result = [];
        $added_this = false;
        foreach ($compound as $simple) {
            // Make sure pseudo selectors always come last.
            if (!$added_this && $simple instanceof Pseudo_Selector) {
                $result[] = $this;
                $added_this = true;
            }
            $result[] = $simple;
        }
        if (!$added_this) {
            $result[] = $this;
        }
        return $result;
    }
    public function is_superselector(Simple_Selector $other): bool
    {
        if ($this === $other || $this->equals($other)) {
            return true;
        }
        if ($other instanceof Pseudo_Selector && $other->is_class()) {
            $list = $other->get_selector();
            if ($list !== null && \in_array($other->get_normalized_name(), self::SUBSELECTOR_PSEUDOS, true)) {
                foreach ($list->get_components() as $complex) {
                    if (\count($complex->get_components()) === 0) {
                        return false;
                    }
                    foreach (List_Util::last($complex->get_components())->get_selector()->get_components() as $simple) {
                        if ($this->is_superselector($simple)) {
                            continue 2;
                        }
                    }
                    return false;
                }
                return true;
            }
        }
        return false;
    }
}