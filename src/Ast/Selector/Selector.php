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

use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Exception;
use Scss_Php\Scss_Php\Serializer\Serializer;
use Scss_Php\Scss_Php\Util\Equatable;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Scss_Php\Scss_Php\Warn;
use Source_Span\File_Span;
/**
 * A node in the abstract syntax tree for a selector.
 *
 * This selector tree is mostly plain CSS, but also may contain a
 * {@see ParentSelector} or a {@see PlaceholderSelector}.
 *
 * Selectors have structural equality semantics.
 *
 * @internal
 */
abstract class Selector implements Ast_Node, Equatable
{
    private readonly File_Span $span;
    public function __construct(File_Span $span)
    {
        $this->span = $span;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    /**
     * Whether this selector, and complex selectors containing it, should not be
     * emitted.
     */
    public function is_invisible(): bool
    {
        return $this->accept(new Is_Invisible_Visitor(true));
    }
    /**
     * Whether this selector would be invisible even if it didn't have bogus
     * combinators.
     */
    public function is_invisible_other_than_bogus_combinators(): bool
    {
        return $this->accept(new Is_Invisible_Visitor(false));
    }
    /**
     * Whether this selector is not valid CSS.
     *
     * This includes both selectors that are useful exclusively for build-time
     * nesting (`> .foo)` and selectors with invalid combinators that are still
     * supported for backwards-compatibility reasons (`.foo + ~ .bar`).
     */
    public function is_bogus(): bool
    {
        return $this->accept(new Is_Bogus_Visitor(true));
    }
    /**
     * Whether this selector is bogus other than having a leading combinator.
     */
    public function is_bogus_other_than_leading_combinator(): bool
    {
        return $this->accept(new Is_Bogus_Visitor(false));
    }
    /**
     * Whether this is a useless selector (that is, it's bogus _and_ it can't be
     * transformed into valid CSS by `@extend` or nesting).
     */
    public function is_useless(): bool
    {
        return $this->accept(new Is_Useless_Visitor());
    }
    /**
     * Prints a warning if $this is a bogus selector.
     *
     * This may only be called from within a custom Sass function. This will
     * throw a {@see SassException} in a future major version.
     */
    public function assert_not_bogus(?string $name = null): void
    {
        if (!$this->is_bogus()) {
            return;
        }
        Warn::for_deprecation(($name === null ? '' : "\${$name}: ") . "{$this} is not valid CSS.\nThis will be an error in Dart Sass 2.0.0.\n\nMore info: https://sass-lang.com/d/bogus-combinators", Deprecation::bogusCombinators);
    }
    /**
     * Calls the appropriate visit method on $visitor.
     *
     * @template T
     *
     * @param SelectorVisitor<T> $visitor
     *
     * @return T
     *
     * @internal
     */
    abstract public function accept(Selector_Visitor $visitor);
    final public function __toString(): string
    {
        return Serializer::serialize_selector($this, true);
    }
}