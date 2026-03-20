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

use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A pseudo-class or pseudo-element selector.
 *
 * The semantics of a specific pseudo selector depends on its name. Some
 * selectors take arguments, including other selectors. Sass manually encodes
 * logic for each pseudo selector that takes a selector as an argument, to
 * ensure that extension and other selector operations work properly.
 *
 * @internal
 */
final class Pseudo_Selector extends Simple_Selector
{
    /**
     * Like {@see name}, but without any vendor prefixes.
     */
    private readonly string $normalized_name;
    private readonly bool $is_class;
    private readonly bool $is_syntactic_class;
    private ?int $specificity = null;
    public function __construct(
        /**
         * The name of this selector.
         */
        private readonly string $name,
        File_Span $span,
        bool $element = false,
        /**
         * The non-selector argument passed to this selector.
         *
         * This is `null` if there's no argument. If {@see argument} and {@see selector} are
         * both non-`null`, the selector follows the argument.
         */
        private readonly ?string $argument = null,
        /**
         * The selector argument passed to this selector.
         *
         * This is `null` if there's no selector. If {@see argument} and {@see selector} are
         * both non-`null`, the selector follows the argument.
         */
        private readonly ?Selector_List $selector = null
    )
    {
        $this->is_class = !$element && !self::is_fake_pseudo_element($this->name);
        $this->is_syntactic_class = !$element;
        $this->normalized_name = Util::unvendor($this->name);
        parent::__construct($span);
    }
    /**
     * Returns whether $name is the name of a pseudo-element that can be written
     * with pseudo-class syntax (`:before`, `:after`, `:first-line`, or
     * `:first-letter`)
     */
    private static function is_fake_pseudo_element(string $name): bool
    {
        if ($name === '') {
            return false;
        }
        switch ($name[0]) {
            case 'a':
            case 'A':
                return strtolower($name) === 'after';
            case 'b':
            case 'B':
                return strtolower($name) === 'before';
            case 'f':
            case 'F':
                $lower_cased_name = strtolower($name);
                return $lower_cased_name === 'first-line' || $lower_cased_name === 'first-letter';
            default:
                return false;
        }
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_normalized_name(): string
    {
        return $this->normalized_name;
    }
    /**
     * Whether this is a pseudo-class selector.
     *
     * This is `true` if and only if {@see isElement} is `false`.
     */
    public function is_class(): bool
    {
        return $this->is_class;
    }
    /**
     * Whether this is a pseudo-element selector.
     *
     * This is `true` if and only if {@see isClass} is `false`.
     */
    public function is_element(): bool
    {
        return !$this->is_class;
    }
    /**
     * Whether this is syntactically a pseudo-class selector.
     *
     * This is the same as {@see isClass} unless this selector is a pseudo-element
     * that was written syntactically as a pseudo-class (`:before`, `:after`,
     * `:first-line`, or `:first-letter`).
     *
     * This is `true` if and only if {@see isSyntacticElement} is `false`.
     */
    public function is_syntactic_class(): bool
    {
        return $this->is_syntactic_class;
    }
    /**
     * Whether this is syntactically a pseudo-element selector.
     *
     * This is `true` if and only if {@see isSyntacticClass} is `false`.
     */
    public function is_syntactic_element(): bool
    {
        return !$this->is_syntactic_class;
    }
    /**
     * Whether this is a valid `:host` selector.
     *
     * @internal
     */
    public function is_host(): bool
    {
        return $this->is_class && $this->name === 'host';
    }
    /**
     * Whether this is a valid `:host-context` selector.
     *
     * @internal
     */
    public function is_host_context(): bool
    {
        return $this->is_class && $this->name === 'host-context' && $this->selector !== null;
    }
    public function get_argument(): ?string
    {
        return $this->argument;
    }
    public function get_selector(): ?Selector_List
    {
        return $this->selector;
    }
    public function get_specificity(): int
    {
        if ($this->specificity === null) {
            $this->specificity = $this->compute_specificity();
        }
        return $this->specificity;
    }
    /**
     * @internal
     */
    public function has_complicated_superselector_semantics(): bool
    {
        if ($this->is_element()) {
            return true;
        }
        return $this->selector !== null;
    }
    private function compute_specificity(): int
    {
        if ($this->is_element()) {
            return 1;
        }
        $selector = $this->selector;
        if ($selector === null) {
            return parent::get_specificity();
        }
        // https://www.w3.org/TR/selectors-4/#specificity-rules
        switch ($this->normalized_name) {
            case 'where':
                return 0;
            case 'is':
            case 'not':
            case 'has':
            case 'matches':
                $max_specificity = 0;
                foreach ($selector->get_components() as $complex) {
                    $max_specificity = max($max_specificity, $complex->get_specificity());
                }
                return $max_specificity;
            case 'nth-child':
            case 'nth-last-child':
                $max_specificity = 0;
                foreach ($selector->get_components() as $complex) {
                    $max_specificity = max($max_specificity, $complex->get_specificity());
                }
                return parent::get_specificity() + $max_specificity;
            default:
                return parent::get_specificity();
        }
    }
    public function with_selector(Selector_List $selector): Pseudo_Selector
    {
        return new Pseudo_Selector($this->name, $this->get_span(), $this->is_element(), $this->argument, $selector);
    }
    public function add_suffix(string $suffix): \Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector
    {
        if ($this->argument !== null || $this->selector !== null) {
            parent::add_suffix($suffix);
        }
        return new Pseudo_Selector($this->name . $suffix, $this->get_span(), $this->is_element());
    }
    public function unify(array $compound): ?array
    {
        if ($this->name === 'host' || $this->name === 'host-context') {
            foreach ($compound as $simple) {
                if (!$simple instanceof Pseudo_Selector || !$simple->is_host() && $simple->selector === null) {
                    return null;
                }
            }
        } elseif (\count($compound) === 1) {
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
            if ($simple instanceof Pseudo_Selector && $simple->is_element()) {
                // A given compound selector may only contain one pseudo element. If
                // $compound has a different one than $this, unification fails.
                if ($this->is_element()) {
                    return null;
                }
                // Otherwise, this is a pseudo selector and should come before pseudo
                // elements.
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
        if (parent::is_superselector($other)) {
            return true;
        }
        $selector = $this->selector;
        if ($selector === null) {
            return $this === $other || $this->equals($other);
        }
        if ($other instanceof Pseudo_Selector && $this->is_element() && $other->is_element() && $this->normalized_name === 'slotted' && $other->name === $this->name) {
            if ($other->get_selector() !== null) {
                return $selector->is_superselector($other->get_selector());
            }
            return false;
        }
        // Fall back to the logic defined in ExtendUtil, which knows how to
        // compare selector pseudoclasses against raw selectors.
        return (new Compound_Selector([$this], $this->get_span()))->is_superselector(new Compound_Selector([$other], $this->get_span()));
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_pseudo_selector($this);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Pseudo_Selector && $other->name === $this->name && $other->is_class === $this->is_class && $other->argument === $this->argument && ($this->selector === $other->selector || $this->selector !== null && $other->selector !== null && $this->selector->equals($other->selector));
    }
}