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
namespace Scss_Php\Scss_Php\Value;

use Jiri_Pudil\Sealed_Classes\Sealed;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Serializer\Serializer;
use Scss_Php\Scss_Php\Util\Equatable;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
use Scss_Php\Scss_Php\Warn;
/**
 * A SassScript value.
 *
 * All SassScript values are unmodifiable. New values can be constructed using
 * subclass constructors like `new SassString`. Untyped values can be cast to
 * particular types using `assert*()` functions like {@see assertString}, which
 * throw user-friendly error messages if they fail.
 */
#[Sealed(permits: [Sass_Boolean::class, Sass_Calculation::class, Sass_Color::class, Sass_Function::class, Sass_List::class, Sass_Map::class, Sass_Mixin::class, Sass_Null::class, Sass_Number::class, Sass_String::class])]
abstract class Value implements Equatable, \Stringable
{
    /**
     * Whether the value counts as `true` in an `@if` statement and other contexts
     */
    public function is_truthy(): bool
    {
        return true;
    }
    /**
     * The separator for this value as a list.
     *
     * All SassScript values can be used as lists. Maps count as lists of pairs,
     * and all other values count as single-value lists.
     */
    public function get_separator(): List_Separator
    {
        return List_Separator::UNDECIDED;
    }
    /**
     * Whether this value as a list has brackets.
     *
     * All SassScript values can be used as lists. Maps count as lists of pairs,
     * and all other values count as single-value lists.
     */
    public function has_brackets(): bool
    {
        return false;
    }
    /**
     * This value as a list.
     *
     * All SassScript values can be used as lists. Maps count as lists of pairs,
     * and all other values count as single-value lists.
     *
     * @return list<Value>
     */
    public function as_list(): array
    {
        return [$this];
    }
    /**
     * The length of {@see asList}.
     *
     * This is used to compute {@see sassIndexToListIndex} without allocating a new
     * list.
     */
    protected function get_length_as_list(): int
    {
        return 1;
    }
    /**
     * Calls the appropriate visit method on $visitor.
     *
     * @template T
     *
     * @param ValueVisitor<T> $visitor
     *
     * @return T
     *
     * @internal
     */
    abstract public function accept(Value_Visitor $visitor);
    /**
     * Converts $sassIndex into a PHP-style index into the list returned by
     * {@see asList}.
     *
     * Sass indexes are one-based, while PHP indexes are zero-based. Sass
     * indexes may also be negative in order to index from the end of the list.
     *
     * @throws SassScriptException if $sassIndex isn't a number, if that
     * number isn't an integer, or if that integer isn't a valid index for
     * {@see asList}. If $sassIndex came from a function argument, $name is the
     * argument name (without the `$`). It's used for error reporting.
     */
    public function sass_index_to_list_index(Value $sass_index, ?string $name = null): int
    {
        $index_value = $sass_index->assert_number($name);
        if ($index_value->has_units()) {
            $message = <<<WARNING
            \${$name}: Passing a number with unit {$index_value->get_unit_string()} is deprecated.
            
            To preserve current behavior: {$index_value->unit_suggestion($name ?? 'index')}
            
            More info: https://sass-lang.com/d/function-units
            WARNING;
            Warn::for_deprecation($message, Deprecation::functionUnits);
        }
        $index = $index_value->assert_int($name);
        if ($index === 0) {
            throw Sass_Script_Exception::for_argument('List index may not be 0.', $name);
        }
        $length_as_list = $this->get_length_as_list();
        if (abs($index) > $length_as_list) {
            throw Sass_Script_Exception::for_argument("Invalid index {$sass_index} for a list with {$length_as_list} elements.", $name);
        }
        return $index < 0 ? $length_as_list + $index : $index - 1;
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a boolean.
     *
     * Note that generally, functions should use {@see isTruthy} rather than requiring
     * a literal boolean.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_boolean(?string $name = null): Sass_Boolean
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a boolean.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a calculation.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_calculation(?string $name = null): Sass_Calculation
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a calculation.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a color.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_color(?string $name = null): Sass_Color
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a color.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a function reference.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_function(?string $name = null): Sass_Function
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a function reference.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a mixin reference.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_mixin(?string $name = null): Sass_Mixin
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a mixin reference.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a map.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_map(?string $name = null): Sass_Map
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a map.", $name);
    }
    /**
     * Return $this as a SassMap if it is one (including empty lists) or null otherwise.
     */
    public function try_map(): ?Sass_Map
    {
        return null;
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a number.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_number(?string $name = null): Sass_Number
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a number.", $name);
    }
    /**
     * Throws a {@see SassScriptException} if $this isn't a string.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @throws SassScriptException
     */
    public function assert_string(?string $name = null): Sass_String
    {
        throw Sass_Script_Exception::for_argument("{$this} is not a string.", $name);
    }
    /**
     * Parses $this as a selector list, in the same manner as the
     * `selector-parse()` function.
     *
     * @throws SassScriptException if this isn't a type that can be parsed as a
     * selector, or if parsing fails. If $allowParent is `true`, this allows
     * {@see ParentSelector}s. Otherwise, they're considered parse errors.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @internal
     */
    public function assert_selector(?string $name = null, bool $allow_parent = false): Selector_List
    {
        $string = $this->selector_string($name);
        try {
            return Selector_List::parse($string, null, null, null, $allow_parent);
        } catch (Sass_Format_Exception $e) {
            throw Sass_Script_Exception::for_argument($e->get_message(), $name, $e);
        }
    }
    /**
     * Parses $this as a simple selector, in the same manner as the
     * `selector-parse()` function.
     *
     * @throws SassScriptException if this isn't a type that can be parsed as a
     * selector, or if parsing fails. If $allowParent is `true`, this allows
     * {@see ParentSelector}s. Otherwise, they're considered parse errors.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @internal
     */
    public function assert_simple_selector(?string $name = null, bool $allow_parent = false): Simple_Selector
    {
        $string = $this->selector_string($name);
        try {
            return Simple_Selector::parse($string, null, null, $allow_parent);
        } catch (Sass_Format_Exception $e) {
            throw Sass_Script_Exception::for_argument($e->get_message(), $name, $e);
        }
    }
    /**
     * Parses $this as a compound selector, in the same manner as the
     * `selector-parse()` function.
     *
     * @throws SassScriptException if this isn't a type that can be parsed as a
     * selector, or if parsing fails. If $allowParent is `true`, this allows
     * {@see ParentSelector}s. Otherwise, they're considered parse errors.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @internal
     */
    public function assert_compound_selector(?string $name = null, bool $allow_parent = false): Compound_Selector
    {
        $string = $this->selector_string($name);
        try {
            return Compound_Selector::parse($string, null, null, $allow_parent);
        } catch (Sass_Format_Exception $e) {
            throw Sass_Script_Exception::for_argument($e->get_message(), $name, $e);
        }
    }
    /**
     * Parses $this as a complex selector, in the same manner as the
     * `selector-parse()` function.
     *
     * @throws SassScriptException if this isn't a type that can be parsed as a
     * selector, or if parsing fails. If $allowParent is `true`, this allows
     * {@see ParentSelector}s. Otherwise, they're considered parse errors.
     *
     * If this came from a function argument, $name is the argument name
     * (without the `$`). It's used for error reporting.
     *
     * @internal
     */
    public function assert_complex_selector(?string $name = null, bool $allow_parent = false): Complex_Selector
    {
        $string = $this->selector_string($name);
        try {
            return Complex_Selector::parse($string, null, null, $allow_parent);
        } catch (Sass_Format_Exception $e) {
            throw Sass_Script_Exception::for_argument($e->get_message(), $name, $e);
        }
    }
    /**
     * Converts a `selector-parse()`-style input into a string that can be
     * parsed.
     *
     * @throws SassScriptException if $this isn't a type or a structure that
     * can be parsed as a selector.
     */
    private function selector_string(?string $name): string
    {
        $string = $this->selector_string_or_null();
        if ($string !== null) {
            return $string;
        }
        throw Sass_Script_Exception::for_argument("{$this} is not a valid selector: it must be a string,\na list of strings, or a list of lists of strings.", $name);
    }
    /**
     * Converts a `selector-parse()`-style input into a string that can be
     * parsed.
     *
     * Returns `null` if $this isn't a type or a structure that can be parsed as
     * a selector.
     */
    private function selector_string_or_null(): ?string
    {
        if ($this instanceof Sass_String) {
            return $this->get_text();
        }
        if (!$this instanceof Sass_List) {
            return null;
        }
        $list = $this;
        if (\count($list->as_list()) === 0) {
            return null;
        }
        $result = [];
        switch ($list->get_separator()) {
            case List_Separator::COMMA:
                foreach ($list->as_list() as $complex) {
                    if ($complex instanceof Sass_String) {
                        $result[] = $complex->get_text();
                    } elseif ($complex instanceof Sass_List && $complex->get_separator() === List_Separator::SPACE) {
                        $string = $complex->selector_string_or_null();
                        if ($string === null) {
                            return null;
                        }
                        $result[] = $string;
                    } else {
                        return null;
                    }
                }
                break;
            case List_Separator::SLASH:
                return null;
            default:
                foreach ($list->as_list() as $compound) {
                    if ($compound instanceof Sass_String) {
                        $result[] = $compound->get_text();
                    } else {
                        return null;
                    }
                }
                break;
        }
        return implode($list->get_separator() === List_Separator::COMMA ? ', ' : ' ', $result);
    }
    /**
     * Whether the value will be represented in CSS as the empty string.
     *
     * @internal
     */
    public function is_blank(): bool
    {
        return false;
    }
    /**
     * Whether this is a value that CSS may treat as a number, such as `calc()` or `var()`.
     *
     * Functions that shadow plain CSS functions need to gracefully handle when
     * these arguments are passed in.
     *
     * @internal
     */
    public function is_special_number(): bool
    {
        return false;
    }
    /**
     * Whether this is a call to `var()`, which may be substituted in CSS for a custom property value.
     *
     * Functions that shadow plain CSS functions need to gracefully handle when
     * these arguments are passed in.
     *
     * @internal
     */
    public function is_var(): bool
    {
        return false;
    }
    /**
     * Returns PHP's `null` value if this is Sass null, and returns `$this` otherwise
     */
    public function real_null(): ?Value
    {
        return $this;
    }
    /**
     * Returns a new list containing $contents that defaults to this value's
     * separator and brackets.
     *
     * @param list<Value> $contents
     */
    public function with_list_contents(array $contents, ?List_Separator $separator = null, ?bool $brackets = null): Sass_List
    {
        return new Sass_List($contents, $separator ?? $this->get_separator(), $brackets ?? $this->has_brackets());
    }
    /**
     * The SassScript = operation
     *
     * @internal
     */
    public function single_equals(Value $other): Value
    {
        return new Sass_String(sprintf('%s=%s', $this->to_css_string(), $other->to_css_string()), false);
    }
    /**
     * The SassScript `>` operation.
     *
     * @internal
     */
    public function greater_than(Value $other): Sass_Boolean
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} > {$other}\".");
    }
    /**
     * The SassScript `>=` operation.
     *
     * @internal
     */
    public function greater_than_or_equals(Value $other): Sass_Boolean
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} >= {$other}\".");
    }
    /**
     * The SassScript `<` operation.
     *
     * @internal
     */
    public function less_than(Value $other): Sass_Boolean
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} < {$other}\".");
    }
    /**
     * The SassScript `<=` operation.
     *
     * @internal
     */
    public function less_than_or_equals(Value $other): Sass_Boolean
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} <= {$other}\".");
    }
    /**
     * The SassScript `*` operation.
     *
     * @internal
     */
    public function times(Value $other): Value
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} * {$other}\".");
    }
    /**
     * The SassScript `%` operation.
     *
     * @internal
     */
    public function modulo(Value $other): Value
    {
        throw new Sass_Script_Exception("Undefined operation \"{$this} % {$other}\".");
    }
    /**
     * The SassScript `+` operation.
     *
     * @internal
     */
    public function plus(Value $other): Value
    {
        if ($other instanceof Sass_String) {
            return new Sass_String($this->to_css_string() . $other->get_text(), $other->has_quotes());
        }
        if ($other instanceof Sass_Calculation) {
            throw new Sass_Script_Exception("Undefined operation \"{$this} + {$other}\".");
        }
        return new Sass_String($this->to_css_string() . $other->to_css_string(), false);
    }
    /**
     * The SassScript `-` operation.
     *
     * @internal
     */
    public function minus(Value $other): Value
    {
        if ($other instanceof Sass_Calculation) {
            throw new Sass_Script_Exception("Undefined operation \"{$this} - {$other}\".");
        }
        return new Sass_String(sprintf('%s-%s', $this->to_css_string(), $other->to_css_string()), false);
    }
    /**
     * The SassScript `/` operation.
     *
     * @internal
     */
    public function divided_by(Value $other): Value
    {
        return new Sass_String(sprintf('%s/%s', $this->to_css_string(), $other->to_css_string()), false);
    }
    /**
     * The SassScript unary `+` operation.
     *
     * @internal
     */
    public function unary_plus(): Value
    {
        return new Sass_String(sprintf('+%s', $this->to_css_string()), false);
    }
    /**
     * The SassScript unary `-` operation.
     *
     * @internal
     */
    public function unary_minus(): Value
    {
        return new Sass_String(sprintf('-%s', $this->to_css_string()), false);
    }
    /**
     * The SassScript unary `/` operation.
     *
     * @internal
     */
    public function unary_divide(): Value
    {
        return new Sass_String(sprintf('/%s', $this->to_css_string()), false);
    }
    /**
     * The SassScript unary `not` operation.
     *
     * @internal
     */
    public function unary_not(): Value
    {
        return Sass_Boolean::create(false);
    }
    /**
     * Returns a copy of $this without {@see SassNumber::$asSlash} set.
     *
     * If this isn't a SassNumber, return it as-is.
     *
     * @internal
     */
    public function without_slash(): Value
    {
        return $this;
    }
    /**
     * Returns a valid CSS representation of $this.
     *
     * Use {@see toString} instead to get a string representation even if this
     * isn't valid CSS.
     *
     * Internal-only: If $quote is `false`, quoted strings are emitted without
     * quotes.
     *
     * @throws SassScriptException if $this cannot be represented in plain CSS.
     */
    final public function to_css_string(bool $quote = true): string
    {
        return Serializer::serialize_value($this, false, $quote);
    }
    /**
     * Returns a Sass representation of $this.
     *
     * Note that this is equivalent to calling `inspect()` on the value, and thus
     * won't reflect the user's output settings. {@see toCssString} should be used
     * instead to convert $this to CSS.
     */
    final public function __toString(): string
    {
        return Serializer::serialize_value($this, true);
    }
}