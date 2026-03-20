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
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Extend\Extend_Util;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Selector_Parser;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A compound selector.
 *
 * A compound selector is composed of {@see SimpleSelector}s. It matches an element
 * that matches all of the component simple selectors.
 *
 * @internal
 */
final class Compound_Selector extends Selector
{
    /**
     * The components of this selector.
     *
     * This is never empty.
     *
     * @var list<SimpleSelector>
     */
    private readonly array $components;
    private ?int $specificity = null;
    private ?bool $complicated_superselector_semantics = null;
    /**
     * Parses a compound selector from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes.
     * $allowParent controls whether a {@see ParentSelector} is allowed in this
     * selector.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null, bool $allow_parent = true): Compound_Selector
    {
        return (new Selector_Parser($contents, $logger, $url, $allow_parent))->parse_compound_selector();
    }
    /**
     * @param list<SimpleSelector> $components
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
     * @return list<SimpleSelector>
     */
    public function get_components(): array
    {
        return $this->components;
    }
    public function get_last_component(): Simple_Selector
    {
        return $this->components[\count($this->components) - 1];
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
                $specificity += $component->get_specificity();
            }
            $this->specificity = $specificity;
        }
        return $this->specificity;
    }
    /**
     * If this compound selector is composed of a single simple selector, returns
     * it.
     *
     * Otherwise, returns null.
     */
    public function get_single_simple(): ?Simple_Selector
    {
        return \count($this->components) === 1 ? $this->components[0] : null;
    }
    /**
     * Whether any simple selector in this contains a selector that requires
     * complex non-local reasoning to determine whether it's a super- or
     * sub-selector.
     *
     * This includes both pseudo-elements and pseudo-selectors that take
     * selectors as arguments.
     *
     * @internal
     */
    public function has_complicated_superselector_semantics(): bool
    {
        return $this->complicated_superselector_semantics ??= Iterable_Util::any($this->components, fn(Simple_Selector $component): bool => $component->has_complicated_superselector_semantics());
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_compound_selector($this);
    }
    /**
     * Whether this is a superselector of $other.
     *
     * That is, whether this matches every element that $other matches, as well
     * as possibly additional elements.
     */
    public function is_superselector(Compound_Selector $other): bool
    {
        return Extend_Util::compound_is_superselector($this, $other);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Compound_Selector && Equatable_Util::list_equals($this->components, $other->components);
    }
}