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
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
use Source_Span\File_Span;
/**
 * A modifiable version of {@see CssDeclaration} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Declaration extends Modifiable_Css_Node implements Css_Declaration
{
    private readonly File_Span $value_span_for_map;
    private readonly File_Span $span;
    /**
     * @param CssValue<string> $name
     * @param CssValue<Value> $value
     * @param list<CssStyleRule> $interleavedRules
     */
    public function __construct(private readonly Css_Value $name, private readonly Css_Value $value, File_Span $span, private readonly bool $parsed_as_custom_property, private readonly array $interleaved_rules = [], private readonly ?Trace $trace = null, ?File_Span $value_span_for_map = null)
    {
        $this->value_span_for_map = $value_span_for_map ?? $this->value->get_span();
        $this->span = $span;
        if ($this->parsed_as_custom_property) {
            if (!$this->is_custom_property()) {
                throw new \InvalidArgumentException('parsedAsCustomProperty must be false if name doesn\'t begin with "--".');
            }
            if (!$this->value->get_value() instanceof Sass_String) {
                throw new \InvalidArgumentException(sprintf('If parsedAsCustomProperty is true, value must contain a SassString (was %s).', get_debug_type($this->value->get_value())));
            }
        }
    }
    public function get_name(): Css_Value
    {
        return $this->name;
    }
    public function get_value(): Css_Value
    {
        return $this->value;
    }
    public function get_interleaved_rules(): array
    {
        return $this->interleaved_rules;
    }
    public function get_trace(): ?Trace
    {
        return $this->trace;
    }
    public function is_parsed_as_custom_property(): bool
    {
        return $this->parsed_as_custom_property;
    }
    public function get_value_span_for_map(): File_Span
    {
        return $this->value_span_for_map;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function is_custom_property(): bool
    {
        return str_starts_with($this->name->get_value(), '--');
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_declaration($this);
    }
}