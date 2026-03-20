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
namespace Scss_Php\Scss_Php\Ast\Css;

use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Scss_Php\Scss_Php\Value\Value;
use Source_Span\File_Span;
/**
 * A plain CSS declaration (that is, a `name: value` pair).
 *
 * @internal
 */
interface Css_Declaration extends Css_Node
{
    /**
     * The name of this declaration.
     *
     * @return CssValue<string>
     */
    public function get_name(): Css_Value;
    /**
     * The value of this declaration.
     *
     * @return CssValue<Value>
     */
    public function get_value(): Css_Value;
    /**
     * A list of style rules that appeared before this declaration in the Sass
     * input but after it in the CSS output.
     *
     * These are used to emit mixed declaration deprecation warnings during
     * serialization, so we can check based on specificity whether the warnings
     * are really necessary without worrying about `@extend` potentially changing
     * things up.
     *
     * @return list<CssStyleRule>
     */
    public function get_interleaved_rules(): array;
    /**
     * The stack trace indicating where this node was created.
     *
     * This is used to emit interleaved declaration warnings, and only needs to be set if
     * {@see getInterleavedRules} isn't empty.
     */
    public function get_trace(): ?Trace;
    /**
     * The span for {@see getValue} that should be emitted to the source map.
     *
     * When the declaration's expression is just a variable, this is the span
     * where that variable was declared whereas `$this->getValue()->getSpan()` is the span where
     * the variable was used. Otherwise, this is identical to `$this->getValue()->getSpan()`.
     */
    public function get_value_span_for_map(): File_Span;
    /**
     * Returns whether this is a CSS Custom Property declaration.
     */
    public function is_custom_property(): bool;
    /**
     * Whether this was originally parsed as a custom property declaration, as
     * opposed to using something like `#{--foo}: ...` to cause it to be parsed
     * as a normal Sass declaration.
     *
     * If this is `true`, {@see isCustomProperty} will also be `true` and {@see getValue} will
     * contain a {@see SassString}.
     */
    public function is_parsed_as_custom_property(): bool;
}