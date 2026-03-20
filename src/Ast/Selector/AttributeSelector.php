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

use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * An attribute selector.
 *
 * This selects for elements with the given attribute, and optionally with a
 * value matching certain conditions as well.
 *
 * @internal
 */
final class Attribute_Selector extends Simple_Selector
{
    /**
     * Creates an attribute selector that matches any element with a property of
     * the given name.
     */
    public static function create(Qualified_Name $name, File_Span $span): Attribute_Selector
    {
        return new Attribute_Selector($name, $span, null, null, null);
    }
    /**
     * Creates an attribute selector that matches an element with a property
     * named $name, whose value matches $value based on the semantics of $op.
     */
    public static function with_operator(Qualified_Name $name, ?Attribute_Operator $op, ?string $value, File_Span $span, ?string $modifier = null): Attribute_Selector
    {
        return new Attribute_Selector($name, $span, $op, $value, $modifier);
    }
    private function __construct(
        /**
         * The name of the attribute being selected for.
         */
        private readonly Qualified_Name $name,
        File_Span $span,
        /**
         * The operator that defines the semantics of {@see value}.
         *
         * If this is `null`, this matches any element with the given property,
         * regardless of this value. It's `null` if and only if {@see value} is `null`.
         */
        private readonly ?Attribute_Operator $op,
        /**
         * An assertion about the value of {@see name}.
         *
         * The precise semantics of this string are defined by {@see op}.
         *
         * If this is `null`, this matches any element with the given property,
         * regardless of this value. It's `null` if and only if {@see op} is `null`.
         */
        private readonly ?string $value,
        /**
         * The modifier which indicates how the attribute selector should be
         * processed.
         *
         * See for example [case-sensitivity][] modifiers.
         *
         * [case-sensitivity]: https://www.w3.org/TR/selectors-4/#attribute-case
         *
         * If {@see op} is `null`, this is always `null` as well.
         */
        private readonly ?string $modifier
    )
    {
        parent::__construct($span);
    }
    public function get_name(): Qualified_Name
    {
        return $this->name;
    }
    public function get_op(): ?Attribute_Operator
    {
        return $this->op;
    }
    public function get_value(): ?string
    {
        return $this->value;
    }
    public function get_modifier(): ?string
    {
        return $this->modifier;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_attribute_selector($this);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Attribute_Selector && $other->name->equals($this->name) && $other->op === $this->op && $other->value === $this->value && $other->modifier === $this->modifier;
    }
}