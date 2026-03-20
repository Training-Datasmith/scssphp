<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Serializer;

use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Comment;
use Scss_Php\Scss_Php\Ast\Css\Css_Declaration;
use Scss_Php\Scss_Php\Ast\Css\Css_Import;
use Scss_Php\Scss_Php\Ast\Css\Css_Keyframe_Block;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Parent_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Css_Supports_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Ast\Selector\Attribute_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Class_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Combinator;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Id_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Parent_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
use Scss_Php\Scss_Php\Colors;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Logger\Quiet_Logger;
use Scss_Php\Scss_Php\Output_Style;
use Scss_Php\Scss_Php\Parser\Line_Scanner;
use Scss_Php\Scss_Php\Parser\Parser;
use Scss_Php\Scss_Php\Parser\String_Scanner;
use Scss_Php\Scss_Php\Source_Span\Multi_Span;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Util\Logger_Util;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Value\Calculation_Operation;
use Scss_Php\Scss_Php\Value\Calculation_Operator;
use Scss_Php\Scss_Php\Value\Color_Format_Enum;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Calculation;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Function;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Mixin;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Span_Color_Format;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Visitor\Css_Visitor;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * @internal
 *
 * @template-implements CssVisitor<void>
 * @template-implements ValueVisitor<void>
 * @template-implements SelectorVisitor<void>
 */
final class Serialize_Visitor implements Css_Visitor, Value_Visitor, Selector_Visitor
{
    private readonly Source_Map_Buffer $buffer;
    /**
     * The current indentation of the CSS output.
     */
    private int $indentation = 0;
    private readonly bool $compressed;
    public function __construct(
        /**
         * Whether we're emitting an unambiguous representation of the source
         * structure, as opposed to valid CSS.
         */
        private readonly bool $inspect = false,
        /**
         * Whether quoted strings should be emitted with quotes.
         */
        private readonly bool $quote = true,
        Output_Style $style = Output_Style::EXPANDED,
        bool $source_map = false,
        private readonly ?Logger_Interface $logger = new Quiet_Logger()
    )
    {
        $this->buffer = $source_map ? new Tracking_Source_Map_Buffer() : new Simple_String_Buffer();
        $this->compressed = $style === Output_Style::COMPRESSED;
    }
    public function get_buffer(): Source_Map_Buffer
    {
        return $this->buffer;
    }
    public function visit_css_stylesheet(Css_Stylesheet $node): void
    {
        $previous = null;
        foreach ($node->get_children() as $child) {
            if ($this->is_invisible($child)) {
                continue;
            }
            if ($previous !== null) {
                if ($this->requires_semicolon($previous)) {
                    $this->buffer->write_char(';');
                }
                if ($this->is_trailing_comment($child, $previous)) {
                    $this->write_optional_space();
                } else {
                    $this->write_line_feed();
                    if ($previous->is_group_end()) {
                        $this->write_line_feed();
                    }
                }
            }
            $previous = $child;
            $child->accept($this);
        }
        if ($previous !== null && $this->requires_semicolon($previous) && !$this->compressed) {
            $this->buffer->write_char(';');
        }
    }
    public function visit_css_comment(Css_Comment $node): void
    {
        $this->for($node, function () use ($node): void {
            // Preserve comments that start with `/*!`.
            if ($this->compressed && !$node->is_preserved()) {
                return;
            }
            // Ignore sourceMappingURL and sourceURL comments.
            if (preg_match('{^/\*# source(Mapping)?URL=}', $node->get_text())) {
                return;
            }
            $minimum_indentation = $this->minimum_indentation($node->get_text());
            assert($minimum_indentation !== -1);
            if ($minimum_indentation === null) {
                $this->write_indentation();
                $this->buffer->write($node->get_text());
                return;
            }
            $minimum_indentation = min($minimum_indentation, $node->get_span()->get_start()->get_column());
            $this->write_indentation();
            $this->write_with_indent($node->get_text(), $minimum_indentation);
        });
    }
    public function visit_css_at_rule(Css_At_Rule $node): void
    {
        $this->write_indentation();
        $this->for($node, function () use ($node): void {
            $this->buffer->write_char('@');
            $this->write($node->get_name());
            $value = $node->get_value();
            if ($value !== null) {
                $this->buffer->write_char(' ');
                $this->write($value);
            }
            if (!$node->is_childless()) {
                $this->write_optional_space();
                $this->visit_children($node);
            }
        });
    }
    public function visit_css_media_rule(Css_Media_Rule $node): void
    {
        $this->write_indentation();
        $this->for($node, function () use ($node): void {
            $this->buffer->write('@media');
            $first_query = $node->get_queries()[0];
            if (!$this->compressed || $first_query->get_modifier() !== null || $first_query->get_type() !== null || \count($first_query->get_conditions()) === 1 && str_starts_with($first_query->get_conditions()[0], '(not ')) {
                $this->buffer->write_char(' ');
            }
            $this->write_between($node->get_queries(), $this->get_comma_separator(), $this->visit_media_query(...));
        });
        $this->write_optional_space();
        $this->visit_children($node);
    }
    public function visit_css_import(Css_Import $node): void
    {
        $this->write_indentation();
        $this->for($node, function () use ($node): void {
            $this->buffer->write('@import');
            $this->write_optional_space();
            $this->for($node->get_url(), function () use ($node): void {
                $this->write_import_url($node->get_url()->get_value());
            });
            if ($node->get_modifiers() !== null) {
                $this->write_optional_space();
                $this->write($node->get_modifiers());
            }
        });
    }
    /**
     * Writes $url, which is an import's URL, to the buffer.
     */
    private function write_import_url(string $url): void
    {
        if (!$this->compressed || $url[0] !== 'u') {
            $this->buffer->write($url);
            return;
        }
        // If this is url(...), remove the surrounding function. This is terser and
        // it allows us to remove whitespace between `@import` and the URL.
        $url_contents = substr($url, 4, \strlen($url) - 5);
        $maybe_quote = $url_contents[0];
        if ($maybe_quote === "'" || $maybe_quote === '"') {
            $this->buffer->write($url_contents);
        } else {
            // If the URL didn't contain quotes, write them manually.
            $this->visit_quoted_string($url_contents);
        }
    }
    public function visit_css_keyframe_block(Css_Keyframe_Block $node): void
    {
        $this->write_indentation();
        $this->for($node->get_selector(), function () use ($node): void {
            $this->write_between($node->get_selector()->get_value(), $this->get_comma_separator(), $this->buffer->write(...));
        });
        $this->write_optional_space();
        $this->visit_children($node);
    }
    private function visit_media_query(Css_Media_Query $query): void
    {
        if ($query->get_modifier() !== null) {
            $this->buffer->write($query->get_modifier());
            $this->buffer->write_char(' ');
        }
        if ($query->get_type() !== null) {
            $this->buffer->write($query->get_type());
            if (\count($query->get_conditions())) {
                $this->buffer->write(' and ');
            }
        }
        if (\count($query->get_conditions()) === 1 && str_starts_with($query->get_conditions()[0], '(not ')) {
            $this->buffer->write('not ');
            $condition = $query->get_conditions()[0];
            $this->buffer->write(substr($condition, \strlen('(not '), \strlen($condition) - (\strlen('(not ') + 1)));
        } else {
            $operator = $query->is_conjunction() ? 'and' : 'or';
            $this->write_between($query->get_conditions(), $this->compressed ? "{$operator} " : " {$operator} ", $this->buffer->write(...));
        }
    }
    public function visit_css_style_rule(Css_Style_Rule $node): void
    {
        $this->write_indentation();
        $this->for($node->get_selector(), function () use ($node): void {
            $node->get_selector()->accept($this);
        });
        $this->write_optional_space();
        $this->visit_children($node);
    }
    public function visit_css_supports_rule(Css_Supports_Rule $node): void
    {
        $this->write_indentation();
        $this->for($node, function () use ($node): void {
            $this->buffer->write('@supports');
            if (!($this->compressed && $node->get_condition()->get_value()[0] === '(')) {
                $this->buffer->write_char(' ');
            }
            $this->write($node->get_condition());
        });
        $this->write_optional_space();
        $this->visit_children($node);
    }
    public function visit_css_declaration(Css_Declaration $node): void
    {
        if ($node->get_interleaved_rules() !== []) {
            \assert($node->get_parent() !== null);
            $decl_specificities = $this->specificities($node->get_parent());
            foreach ($node->get_interleaved_rules() as $rule) {
                $rule_specificities = $this->specificities($rule);
                // If the declaration can never match with the same specificity as one
                // of its sibling rules, then ordering will never matter and there's no
                // need to warn about the declaration being re-ordered.
                if (!Iterable_Util::any($decl_specificities, fn($s): bool => \in_array($s, $rule_specificities, true))) {
                    continue;
                }
                Logger_Util::warn_for_deprecation($this->logger, Deprecation::mixedDecls, <<<'MESSAGE'
                Sass's behavior for declarations that appear after nested
                rules will be changing to match the behavior specified by CSS in an upcoming
                version. To keep the existing behavior, move the declaration above the nested
                rule. To opt into the new behavior, wrap the declaration in `& {}`.
                
                More info: https://sass-lang.com/d/mixed-decls
                MESSAGE, new Multi_Span($node->get_span(), 'declaration', ['nested rule' => $rule->get_span()]), $node->get_trace());
            }
        }
        $this->write_indentation();
        $this->write($node->get_name());
        $this->buffer->write_char(':');
        // If `node` is a custom property that was parsed as a normal Sass-syntax
        // property (such as `#{--foo}: ...`), we serialize its value using the
        // normal Sass property logic as well.
        if ($node->is_custom_property() && $node->is_parsed_as_custom_property()) {
            $this->for($node->get_value(), function () use ($node): void {
                if ($this->compressed) {
                    $this->write_folded_value($node);
                } else {
                    $this->write_reindented_value($node);
                }
            });
        } else {
            $this->write_optional_space();
            try {
                $this->buffer->for_span($node->get_value_span_for_map(), fn() => $node->get_value()->get_value()->accept($this));
            } catch (Sass_Script_Exception $error) {
                throw $error->with_span($node->get_value()->get_span());
            }
        }
    }
    /**
     * Returns the set of possible specificities with which $node might match.
     *
     * @return non-empty-array<int>
     */
    private function specificities(Css_Parent_Node $node): array
    {
        if ($node instanceof Css_Style_Rule) {
            // Plain CSS style rule nesting implicitly wraps parent selectors in
            // `:is()`, so they all match with the highest specificity among any of
            // them.
            if ($node->get_parent() !== null) {
                $parent = max($this->specificities($node->get_parent()));
            } else {
                $parent = 0;
            }
            return array_map(fn(Complex_Selector $selector): float|int => $parent + $selector->get_specificity(), $node->get_selector()->get_components());
        }
        if ($node->get_parent() !== null) {
            return $this->specificities($node->get_parent());
        }
        return [0];
    }
    /**
     * Emits the value of $node, with all newlines followed by whitespace
     */
    private function write_folded_value(Css_Declaration $node): void
    {
        $value = $node->get_value()->get_value();
        assert($value instanceof Sass_String);
        $scannner = new String_Scanner($value->get_text());
        while (!$scannner->is_done()) {
            $next = $scannner->read_utf8char();
            if ($next !== "\n") {
                $this->buffer->write_char($next);
                continue;
            }
            $this->buffer->write_char(' ');
            while (Character::is_whitespace($scannner->peek_char())) {
                $scannner->read_char();
            }
        }
    }
    /**
     * Emits the value of $node, re-indented relative to the current indentation.
     */
    private function write_reindented_value(Css_Declaration $node): void
    {
        $node_value = $node->get_value()->get_value();
        assert($node_value instanceof Sass_String);
        $value = $node_value->get_text();
        $minimum_indentation = $this->minimum_indentation($value);
        if ($minimum_indentation === null) {
            $this->buffer->write($value);
            return;
        }
        if ($minimum_indentation === -1) {
            $this->buffer->write(String_Util::trim_ascii_right($value, true));
            $this->buffer->write_char(' ');
            return;
        }
        $minimum_indentation = min($minimum_indentation, $node->get_name()->get_span()->get_start()->get_column());
        $this->write_with_indent($value, $minimum_indentation);
    }
    /**
     * Returns the indentation level of the least-indented non-empty line in
     * $text after the first.
     *
     * Returns `null` if $text contains no newlines, and -1 if it contains
     * newlines but no lines are indented.
     */
    private function minimum_indentation(string $text): ?int
    {
        $scanner = new Line_Scanner($text);
        while (!$scanner->is_done() && $scanner->read_char() !== "\n") {
        }
        if ($scanner->is_done()) {
            return $scanner->peek_char(-1) === "\n" ? -1 : null;
        }
        $min = null;
        while (!$scanner->is_done()) {
            while (!$scanner->is_done()) {
                $next = $scanner->peek_char();
                if ($next !== ' ' && $next !== "\t") {
                    break;
                }
                $scanner->read_char();
            }
            if ($scanner->is_done()) {
                continue;
            }
            if ($scanner->scan_char("\n")) {
                continue;
            }
            $min = $min === null ? $scanner->get_column() : min($min, $scanner->get_column());
            while (!$scanner->is_done() && $scanner->read_char() !== "\n") {
            }
        }
        return $min ?? -1;
    }
    /**
     * Writes $text to {@see buffer}, replacing $minimumIndentation with
     * {@see indentation} for each non-empty line after the first.
     *
     * Compresses trailing empty lines of $text into a single trailing space.
     */
    private function write_with_indent(string $text, int $minimum_indentation): void
    {
        $scanner = new Line_Scanner($text);
        while (!$scanner->is_done()) {
            $next = $scanner->read_char();
            if ($next === "\n") {
                break;
            }
            $this->buffer->write_char($next);
        }
        while (true) {
            assert(Character::is_whitespace($scanner->peek_char(-1)));
            // Scan forward until we hit non-whitespace or the end of [text].
            $line_start = $scanner->get_position();
            $newlines = 1;
            while (true) {
                // If we hit the end of $text, we still need to preserve the fact that
                // whitespace exists because it could matter for custom properties.
                if ($scanner->is_done()) {
                    $this->buffer->write_char(' ');
                    return;
                }
                $next = $scanner->read_char();
                if ($next === ' ') {
                    continue;
                }
                if ($next === "\t") {
                    continue;
                }
                if ($next !== "\n") {
                    break;
                }
                $line_start = $scanner->get_position();
                $newlines++;
            }
            $this->write_times("\n", $newlines);
            $this->write_indentation();
            $this->buffer->write($scanner->substring($line_start + $minimum_indentation));
            // Scan and write until we hit a newline or the end of $text.
            while (true) {
                if ($scanner->is_done()) {
                    return;
                }
                $next = $scanner->read_char();
                if ($next === "\n") {
                    break;
                }
                $this->buffer->write_char($next);
            }
        }
    }
    // ## Values
    public function visit_boolean(Sass_Boolean $value): void
    {
        $this->buffer->write($value->get_value() ? 'true' : 'false');
    }
    public function visit_calculation(Sass_Calculation $value): void
    {
        $this->buffer->write($value->get_name());
        $this->buffer->write_char('(');
        $is_first = true;
        foreach ($value->get_arguments() as $argument) {
            if ($is_first) {
                $is_first = false;
            } else {
                $this->buffer->write($this->get_comma_separator());
            }
            $this->write_calculation_value($argument);
        }
        $this->buffer->write_char(')');
    }
    private function write_calculation_value(object $value): void
    {
        if ($value instanceof Sass_Number && $value->has_complex_units() && !$this->inspect) {
            throw new Sass_Script_Exception("{$value} isn't a valid CSS value.");
        }
        if ($value instanceof Sass_Number && !is_finite($value->get_value())) {
            if (is_nan($value->get_value())) {
                $this->buffer->write('NaN');
            } elseif ($value->get_value() > 0) {
                $this->buffer->write('infinity');
            } else {
                $this->buffer->write('-infinity');
            }
            $this->write_calculation_units($value->get_numerator_units(), $value->get_denominator_units());
        } elseif ($value instanceof Sass_Number && $value->has_complex_units()) {
            $this->write_number($value->get_value());
            $first_unit = $value->get_numerator_units()[0] ?? null;
            if ($first_unit !== null) {
                $this->buffer->write($first_unit);
                $this->write_calculation_units(array_slice($value->get_numerator_units(), 1), $value->get_denominator_units());
            } else {
                $this->write_calculation_units([], $value->get_denominator_units());
            }
        } elseif ($value instanceof Value) {
            $value->accept($this);
        } elseif ($value instanceof Calculation_Operation) {
            $left = $value->get_left();
            $parenthesize_left = $left instanceof Calculation_Operation && $left->get_operator()->get_precedence() < $value->get_operator()->get_precedence();
            if ($parenthesize_left) {
                $this->buffer->write_char('(');
            }
            $this->write_calculation_value($left);
            if ($parenthesize_left) {
                $this->buffer->write_char(')');
            }
            $operator_whitespace = !$this->compressed || $value->get_operator()->get_precedence() === 1;
            if ($operator_whitespace) {
                $this->buffer->write_char(' ');
            }
            $this->buffer->write($value->get_operator()->get_operator());
            if ($operator_whitespace) {
                $this->buffer->write_char(' ');
            }
            $right = $value->get_right();
            $parenthesize_right = $right instanceof Calculation_Operation && $this->parenthesize_calculation_rhs($value->get_operator(), $right->get_operator()) || $value->get_operator() === Calculation_Operator::DIVIDED_BY && $right instanceof Sass_Number && (is_finite($right->get_value()) ? $right->has_complex_units() : $right->has_units());
            if ($parenthesize_right) {
                $this->buffer->write_char('(');
            }
            $this->write_calculation_value($right);
            if ($parenthesize_right) {
                $this->buffer->write_char(')');
            }
        }
    }
    /**
     * Writes the complex numerator and denominator units beyond the first
     * numerator unit for a number as they appear in a calculation.
     *
     * @param list<string> $numeratorUnits
     * @param list<string> $denominatorUnits
     */
    private function write_calculation_units(array $numerator_units, array $denominator_units): void
    {
        foreach ($numerator_units as $unit) {
            $this->write_optional_space();
            $this->buffer->write_char('*');
            $this->write_optional_space();
            $this->buffer->write_char('1');
            $this->buffer->write($unit);
        }
        foreach ($denominator_units as $unit) {
            $this->write_optional_space();
            $this->buffer->write_char('/');
            $this->write_optional_space();
            $this->buffer->write_char('1');
            $this->buffer->write($unit);
        }
    }
    /**
     * Returns whether the right-hand operation of a calculation should be
     * parenthesized.
     *
     * In `a ? (b # c)`, `outer` is `?` and `right` is `#`.
     */
    private function parenthesize_calculation_rhs(Calculation_Operator $outer, Calculation_Operator $right): bool
    {
        if ($outer === Calculation_Operator::DIVIDED_BY) {
            return true;
        }
        if ($outer === Calculation_Operator::PLUS) {
            return false;
        }
        return $right === Calculation_Operator::PLUS || $right === Calculation_Operator::MINUS;
    }
    public function visit_color(Sass_Color $value): void
    {
        $name = Colors::rg_ba_to_color_name($value->get_red(), $value->get_green(), $value->get_blue(), $value->get_alpha());
        // In compressed mode, emit colors in the shortest representation possible.
        if ($this->compressed) {
            if (!Number_Util::fuzzy_equals($value->get_alpha(), 1)) {
                $this->write_rgb($value);
            } else {
                $can_use_short_hex = $this->can_use_short_hex($value);
                $hex_length = $can_use_short_hex ? 4 : 7;
                if ($name !== null && \strlen($name) <= $hex_length) {
                    $this->buffer->write($name);
                } elseif ($can_use_short_hex) {
                    $this->buffer->write_char('#');
                    $this->buffer->write_char(dechex($value->get_red() & 0xf));
                    $this->buffer->write_char(dechex($value->get_green() & 0xf));
                    $this->buffer->write_char(dechex($value->get_blue() & 0xf));
                } else {
                    $this->buffer->write_char('#');
                    $this->write_hex_component($value->get_red());
                    $this->write_hex_component($value->get_green());
                    $this->write_hex_component($value->get_blue());
                }
            }
            return;
        }
        $format = $value->get_format();
        if ($format !== null) {
            if ($format === Color_Format_Enum::rgbFunction) {
                $this->write_rgb($value);
            } elseif ($format === Color_Format_Enum::hslFunction) {
                $this->write_hsl($value);
            } elseif ($format instanceof Span_Color_Format) {
                $this->buffer->write($format->get_original());
            } else {
                // should not happen as our interface is sealed.
                \assert(false, 'unknown format');
            }
        } elseif ($name !== null && !Number_Util::fuzzy_equals($value->get_alpha(), 0)) {
            $this->buffer->write($name);
        } elseif (Number_Util::fuzzy_equals($value->get_alpha(), 1)) {
            $this->buffer->write_char('#');
            $this->write_hex_component($value->get_red());
            $this->write_hex_component($value->get_green());
            $this->write_hex_component($value->get_blue());
        } else {
            $this->write_rgb($value);
        }
    }
    /**
     * Writes $value as an `rgb` or `rgba` function.
     */
    private function write_rgb(Sass_Color $value): void
    {
        $opaque = Number_Util::fuzzy_equals($value->get_alpha(), 1);
        $this->buffer->write($opaque ? 'rgb(' : 'rgba(');
        $this->buffer->write((string) $value->get_red());
        $this->buffer->write($this->get_comma_separator());
        $this->buffer->write((string) $value->get_green());
        $this->buffer->write($this->get_comma_separator());
        $this->buffer->write((string) $value->get_blue());
        if (!$opaque) {
            $this->buffer->write($this->get_comma_separator());
            $this->write_number($value->get_alpha());
        }
        $this->buffer->write_char(')');
    }
    /**
     * Writes $value as an `hsl` or `hsla` function.
     */
    private function write_hsl(Sass_Color $value): void
    {
        $opaque = Number_Util::fuzzy_equals($value->get_alpha(), 1);
        $this->buffer->write($opaque ? 'hsl(' : 'hsla(');
        $this->write_number($value->get_hue());
        $this->buffer->write($this->get_comma_separator());
        $this->write_number($value->get_saturation());
        $this->buffer->write_char('%');
        $this->buffer->write($this->get_comma_separator());
        $this->write_number($value->get_lightness());
        $this->buffer->write_char('%');
        if (!$opaque) {
            $this->buffer->write($this->get_comma_separator());
            $this->write_number($value->get_alpha());
        }
        $this->buffer->write_char(')');
    }
    /**
     * Returns whether $color's hex pair representation is symmetrical (e.g. `FF`).
     */
    private function is_symmetrical_hex(int $color): bool
    {
        return ($color & 0xf) === $color >> 4;
    }
    /**
     * Returns whether $color can be represented as a short hexadecimal color
     * (e.g. `#fff`).
     */
    private function can_use_short_hex(Sass_Color $color): bool
    {
        return $this->is_symmetrical_hex($color->get_red()) && $this->is_symmetrical_hex($color->get_green()) && $this->is_symmetrical_hex($color->get_blue());
    }
    /**
     * Emits $color as a hex character pair.
     */
    private function write_hex_component(int $color): void
    {
        $this->buffer->write(str_pad(dechex($color), 2, '0', STR_PAD_LEFT));
    }
    public function visit_function(Sass_Function $value): void
    {
        if (!$this->inspect) {
            throw new Sass_Script_Exception("{$value} isn't a valid CSS value.");
        }
        $this->buffer->write('get-function(');
        $this->visit_quoted_string($value->get_callable()->get_name());
        $this->buffer->write_char(')');
    }
    public function visit_mixin(Sass_Mixin $value): void
    {
        if (!$this->inspect) {
            throw new Sass_Script_Exception("{$value} isn't a valid CSS value.");
        }
        $this->buffer->write('get-mixin(');
        $this->visit_quoted_string($value->get_callable()->get_name());
        $this->buffer->write_char(')');
    }
    public function visit_list(Sass_List $value): void
    {
        if ($value->has_brackets()) {
            $this->buffer->write_char('[');
        } elseif (\count($value->as_list()) === 0) {
            if (!$this->inspect) {
                throw new Sass_Script_Exception("() isn't a valid CSS value.");
            }
            $this->buffer->write('()');
            return;
        }
        $singleton = $this->inspect && \count($value->as_list()) === 1 && ($value->get_separator() === List_Separator::COMMA || $value->get_separator() === List_Separator::SLASH);
        if ($singleton && !$value->has_brackets()) {
            $this->buffer->write_char('(');
        }
        $separator = $this->separator_string($value->get_separator());
        $is_first = true;
        foreach ($value->as_list() as $element) {
            if (!$this->inspect && $element->is_blank()) {
                continue;
            }
            if ($is_first) {
                $is_first = false;
            } else {
                $this->buffer->write($separator);
            }
            $needs_parens = $this->inspect && self::element_needs_parens($value->get_separator(), $element);
            if ($needs_parens) {
                $this->buffer->write_char('(');
            }
            $element->accept($this);
            if ($needs_parens) {
                $this->buffer->write_char(')');
            }
        }
        if ($singleton) {
            \assert($value->get_separator()->get_separator() !== null, 'The list separator is not undecided at that point.');
            $this->buffer->write($value->get_separator()->get_separator());
            if (!$value->has_brackets()) {
                $this->buffer->write_char(')');
            }
        }
        if ($value->has_brackets()) {
            $this->buffer->write_char(']');
        }
    }
    private function separator_string(List_Separator $separator): string
    {
        return match ($separator) {
            List_Separator::COMMA => $this->get_comma_separator(),
            List_Separator::SLASH => $this->compressed ? '/' : ' / ',
            List_Separator::SPACE => ' ',
            /**
             * This should never be used, but it may still be returned since
             * {@see separatorString} is invoked eagerly by {@see writeList} even for lists
             * with only one element.
             */
            default => '',
        };
    }
    /**
     * Returns whether the value needs parentheses as an element in a list with the given separator.
     */
    private static function element_needs_parens(List_Separator $separator, Value $value): bool
    {
        if (!$value instanceof Sass_List) {
            return false;
        }
        if (count($value->as_list()) < 2) {
            return false;
        }
        if ($value->has_brackets()) {
            return false;
        }
        return match ($separator) {
            List_Separator::COMMA => $value->get_separator() === List_Separator::COMMA,
            List_Separator::SLASH => $value->get_separator() === List_Separator::COMMA || $value->get_separator() === List_Separator::SLASH,
            default => $value->get_separator() !== List_Separator::UNDECIDED,
        };
    }
    public function visit_map(Sass_Map $value): void
    {
        if (!$this->inspect) {
            throw new Sass_Script_Exception("{$value} isn't a valid CSS value.");
        }
        $this->buffer->write_char('(');
        $is_first = true;
        foreach ($value->get_contents() as $key => $element) {
            if ($is_first) {
                $is_first = false;
            } else {
                $this->buffer->write(', ');
            }
            $this->write_map_element($key);
            $this->buffer->write(': ');
            $this->write_map_element($element);
        }
        $this->buffer->write_char(')');
    }
    private function write_map_element(Value $value): void
    {
        $needs_parens = $value instanceof Sass_List && List_Separator::COMMA === $value->get_separator() && !$value->has_brackets();
        if ($needs_parens) {
            $this->buffer->write_char('(');
        }
        $value->accept($this);
        if ($needs_parens) {
            $this->buffer->write_char(')');
        }
    }
    public function visit_null(): void
    {
        if ($this->inspect) {
            $this->buffer->write('null');
        }
    }
    public function visit_number(Sass_Number $value): void
    {
        $as_slash = $value->get_as_slash();
        if ($as_slash !== null) {
            $this->visit_number($as_slash[0]);
            $this->buffer->write_char('/');
            $this->visit_number($as_slash[1]);
            return;
        }
        if (!is_finite($value->get_value())) {
            $this->visit_calculation(Sass_Calculation::unsimplified('calc', [$value]));
            return;
        }
        if ($value->has_complex_units()) {
            if (!$this->inspect) {
                throw new Sass_Script_Exception("{$value} isn't a valid CSS value.");
            }
            $this->visit_calculation(Sass_Calculation::unsimplified('calc', [$value]));
        } else {
            $this->write_number($value->get_value());
            if (\count($value->get_numerator_units()) > 0) {
                $this->buffer->write($value->get_numerator_units()[0]);
            }
        }
    }
    /**
     * Writes $number without exponent notation and with at most
     * {@see SassNumber::PRECISION} digits after the decimal point.
     */
    private function write_number(float $number): void
    {
        if (is_nan($number)) {
            $this->buffer->write('NaN');
            return;
        }
        if ($number === INF) {
            $this->buffer->write('Infinity');
            return;
        }
        if ($number === -INF) {
            $this->buffer->write('-Infinity');
            return;
        }
        $int = Number_Util::fuzzy_as_int($number);
        if ($int !== null) {
            $this->buffer->write((string) $int);
            return;
        }
        $text = $this->remove_exponent((string) $number);
        // Any double that's less than `SassNumber.precision + 2` digits long is
        // guaranteed to be safe to emit directly, since it'll contain at most `0.`
        // followed by [SassNumber.precision] digits.
        $can_write_directly = \strlen($text) < Sass_Number::PRECISION + 2;
        if ($can_write_directly) {
            if ($this->compressed && $text[0] === '0') {
                $text = substr($text, 1);
            }
            $this->buffer->write($text);
            return;
        }
        $this->write_rounded($text);
    }
    /**
     * If $text is written in exponent notation, returns a string representation
     * of it without exponent notation.
     *
     * Otherwise, returns $text as-is.
     */
    private function remove_exponent(string $text): string
    {
        $exponent_delimiter_position = strpos($text, 'E');
        if ($exponent_delimiter_position === false) {
            return $text;
        }
        $negative = $text[0] === '-';
        $buffer = $text[0];
        // If the number has more than one significant digit, the second
        // character will be a decimal point that we don't want to include in
        // the generated number.
        if ($negative) {
            $buffer .= $text[1];
            if ($exponent_delimiter_position > 3) {
                $buffer .= substr($text, 3, $exponent_delimiter_position - 3);
            }
        } elseif ($exponent_delimiter_position > 2) {
            $buffer .= substr($text, 2, $exponent_delimiter_position - 2);
        }
        $exponent = intval(substr($text, $exponent_delimiter_position + 1));
        if ($exponent > 0) {
            // Write an additional zero for each exponent digits other than those
            // already written to the buffer. We subtract 1 from `buffer.length`
            // because the first digit doesn't count towards the exponent. Subtract 1
            // more for negative numbers because of the `-` written to the buffer.
            $additional_zeroes = $exponent - (\strlen($buffer) - 1 - ($negative ? 1 : 0));
            return $buffer . str_repeat('0', $additional_zeroes);
        }
        $result = '';
        if ($negative) {
            $result .= '-';
        }
        $result .= '0.';
        for ($i = -1; $i > $exponent; --$i) {
            $result .= '0';
        }
        $result .= $negative ? substr($buffer, 1) : $buffer;
        return $result;
    }
    /**
     * Assuming $text is a number written without exponent notation, rounds it
     * to {@see SassNumber::PRECISION} digits after the decimal and writes the result
     * to {@see $buffer}.
     */
    private function write_rounded(string $text): void
    {
        \assert(preg_match('/^-?\d+(\.\d+)?$/D', $text) === 1, "\"{$text}\" should be a number written without exponent notation.");
        // We need to ensure that we write at most [SassNumber.precision] digits
        // after the decimal point, and that we round appropriately if necessary. To
        // do this, we maintain an intermediate buffer of digits (both before and
        // after the decimal point), which we then write to [_buffer] as text. We
        // start writing after the first digit to give us room to round up to a
        // higher decimal place than was represented in the original number.
        $digits = array_fill(0, \strlen($text) + 1, 0);
        $digits_index = 1;
        // Write the digits before the decimal to $digits.
        $text_index = 0;
        $negative = $text[0] === '-';
        if ($negative) {
            $text_index++;
        }
        while (true) {
            if ($text_index === \strlen($text)) {
                // If we get here, $text has no decimal point. It definitely doesn't
                // need to be rounded; we can write it as-is.
                $this->buffer->write($text);
                return;
            }
            $code_unit = $text[$text_index++];
            if ($code_unit === '.') {
                break;
            }
            $digits[$digits_index++] = intval($code_unit);
        }
        $first_fractional_digit = $digits_index;
        // Only write at most PRECISION digits after the decimal. If there aren't
        // that many digits left in the number, write it as-is since no rounding or
        // truncation is needed.
        $index_after_precision = $text_index + Sass_Number::PRECISION;
        if ($index_after_precision >= \strlen($text)) {
            $this->buffer->write($text);
            return;
        }
        // Write the digits after the decimal to $digits.
        while ($text_index < $index_after_precision) {
            $digits[$digits_index++] = intval($text[$text_index++]);
        }
        // Round the trailing digits in $digits up if necessary.
        if (intval($text[$text_index]) >= 5) {
            while (true) {
                // $digitsIndex is guaranteed to be >0 here because we added a leading
                // 0 to $digits when we constructed it, so even if we round everything
                // up $newDigit will always be 1 when $digitsIndex is 1.
                $new_digit = ++$digits[$digits_index - 1];
                if ($new_digit !== 10) {
                    break;
                }
                $digits_index--;
            }
        }
        // At most one of the following loops will actually execute. If we rounded
        // digits up before the decimal point, the first loop will set those digits
        // to 0 (rather than 10, which is not a valid decimal digit). On the other
        // hand, if we have trailing zeros left after the decimal point, the second
        // loop will move $digitsIndex before them and cause them not to be
        // written. Either way, $digitsIndex will end up >= $firstFractionalDigit.
        for (; $digits_index < $first_fractional_digit; $digits_index++) {
            $digits[$digits_index] = 0;
        }
        while ($digits_index > $first_fractional_digit && $digits[$digits_index - 1] === 0) {
            $digits_index--;
        }
        // Omit the minus sign if the number ended up being rounded to exactly zero,
        // write "0" explicit to avoid adding a minus sign or omitting the number
        // entirely in compressed mode.
        if ($digits_index === 2 && $digits[0] === 0 && $digits[1] == 0) {
            $this->buffer->write_char('0');
            return;
        }
        if ($negative) {
            $this->buffer->write_char('-');
        }
        // Write the digits before the decimal point to $buffer. Omit the leading
        // 0 that's added to $digits to accommodate rounding, and in compressed
        // mode omit the 0 before the decimal point as well.
        $written_index = 0;
        if ($digits[0] === 0) {
            $written_index++;
            if ($this->compressed && $digits[1] === 0) {
                $written_index++;
            }
        }
        for (; $written_index < $first_fractional_digit; $written_index++) {
            $this->buffer->write_char((string) $digits[$written_index]);
        }
        if ($digits_index > $first_fractional_digit) {
            $this->buffer->write_char('.');
            for (; $written_index < $digits_index; $written_index++) {
                $this->buffer->write_char((string) $digits[$written_index]);
            }
        }
    }
    public function visit_string(Sass_String $value): void
    {
        if ($this->quote && $value->has_quotes()) {
            $this->visit_quoted_string($value->get_text());
        } else {
            $this->visit_unquoted_string($value->get_text());
        }
    }
    private function visit_quoted_string(string $string): void
    {
        $includes_double_quote = str_contains($string, '"');
        $includes_single_quote = str_contains($string, '\'');
        $force_double_quotes = $includes_single_quote && $includes_double_quote;
        $quote = $force_double_quotes || !$includes_double_quote ? '"' : "'";
        $this->buffer->write_char($quote);
        $length = \strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $char = $string[$i];
            switch ($char) {
                case "'":
                    $this->buffer->write_char("'");
                    // such string is always rendered double-quoted
                    break;
                case '"':
                    if ($force_double_quotes) {
                        $this->buffer->write_char('\\');
                    }
                    $this->buffer->write_char('"');
                    break;
                case "\x00":
                case "\x01":
                case "\x02":
                case "\x03":
                case "\x04":
                case "\x05":
                case "\x06":
                case "\x07":
                case "\x08":
                case "\n":
                case "\v":
                case "\f":
                case "\r":
                case "\x0e":
                case "\x0f":
                case "\x10":
                case "\x11":
                case "\x12":
                case "\x13":
                case "\x14":
                case "\x15":
                case "\x16":
                case "\x17":
                case "\x18":
                case "\x19":
                case "\x1a":
                case "\x1b":
                case "\x1c":
                case "\x1d":
                case "\x1e":
                case "\x1f":
                case "":
                    $this->write_escape($this->buffer, $char, $string, $i);
                    break;
                case '\\':
                    $this->buffer->write_char('\\');
                    $this->buffer->write_char('\\');
                    break;
                default:
                    $new_index = $this->try_private_use_character($this->buffer, $char, $string, $i);
                    if ($new_index !== null) {
                        $i = $new_index;
                        break;
                    }
                    $this->buffer->write_char($char);
                    break;
            }
        }
        $this->buffer->write_char($quote);
    }
    private function visit_unquoted_string(string $string): void
    {
        $after_newline = false;
        $length = \strlen($string);
        for ($i = 0; $i < $length; ++$i) {
            $char = $string[$i];
            switch ($char) {
                case "\n":
                    $this->buffer->write_char(' ');
                    $after_newline = true;
                    break;
                case ' ':
                    if (!$after_newline) {
                        $this->buffer->write_char(' ');
                    }
                    break;
                default:
                    $after_newline = false;
                    $new_index = $this->try_private_use_character($this->buffer, $char, $string, $i);
                    if ($new_index !== null) {
                        $i = $new_index;
                        break;
                    }
                    $this->buffer->write_char($char);
                    break;
            }
        }
    }
    /**
     * If $char is the beginning of a private-use character and Sass isn't
     * emitting compressed CSS, writes that character as an escape to $buffer.
     *
     * The $string is the string from which $char was read, and $i is the
     * index it was read from. If this successfully writes the character, returns
     * the index of the *last* byte that was consumed for it. Otherwise,
     * returns `null`.
     *
     * In expanded mode, we print all characters in Private Use Areas as escape
     * codes since there's no useful way to render them directly. These
     * characters are often used for glyph fonts, where it's useful for readers
     * to be able to distinguish between them in the rendered stylesheet.
     */
    private function try_private_use_character(Source_Map_Buffer $buffer, string $char, string $string, int $i): ?int
    {
        if ($this->compressed) {
            return null;
        }
        $first_byte_code = \ord($char);
        if ($first_byte_code >= 0xf0) {
            $extra_bytes = 3;
            // 4-bytes chars
        } elseif ($first_byte_code >= 0xe0) {
            $extra_bytes = 2;
            // 3-bytes chars
        } elseif ($first_byte_code >= 0xc2) {
            $extra_bytes = 1;
            // 2-bytes chars
        } elseif ($first_byte_code >= 0x80 && $first_byte_code <= 0x8f) {
            return null;
            // Continuation of a UTF-8 char started in a previous byte
        } else {
            $extra_bytes = 0;
        }
        if (\strlen($string) <= $i + $extra_bytes) {
            return null;
            // Invalid UTF-8 chars
        }
        if ($extra_bytes) {
            $full_char = substr($string, $i, $extra_bytes + 1);
            $char_code = mb_ord($full_char, 'UTF-8');
        } else {
            $full_char = $char;
            $char_code = $first_byte_code;
        }
        if ($char_code >= 0xe000 && $char_code <= 0xf8ff || $char_code >= 0xf0000 && $char_code <= 0x10ffff) {
            $this->write_escape($buffer, $full_char, $string, $i + $extra_bytes);
            return $i + $extra_bytes;
        }
        return null;
    }
    /**
     * Writes $character as a hexadecimal escape sequence to $buffer.
     *
     * The $string is the string from which the escape is being written, and $i
     * is the index of the last byte of $character in that string. These
     * are used to write a trailing space after the escape if necessary to
     * disambiguate it from the next character.
     */
    private function write_escape(Source_Map_Buffer $buffer, string $character, string $string, int $i): void
    {
        $buffer->write_char('\\');
        $buffer->write(dechex(mb_ord($character, 'UTF-8')));
        if (\strlen($string) === $i + 1) {
            return;
        }
        $next = $string[$i + 1];
        if ($next === ' ' || $next === "\t" || Character::is_hex($next)) {
            $buffer->write_char(' ');
        }
    }
    // ## Selectors
    public function visit_attribute_selector(Attribute_Selector $attribute): void
    {
        $this->buffer->write_char('[');
        $this->buffer->write($attribute->get_name());
        $value = $attribute->get_value();
        if ($value !== null) {
            assert($attribute->get_op() !== null);
            $this->buffer->write($attribute->get_op()->get_text());
            // Emit identifiers that start with `--` with quotes, because IE11
            // doesn't consider them to be valid identifiers.
            if (Parser::is_identifier($value) && !str_starts_with($value, '--')) {
                $this->buffer->write($value);
                if ($attribute->get_modifier() !== null) {
                    $this->buffer->write_char(' ');
                }
            } else {
                $this->visit_quoted_string($value);
                if ($attribute->get_modifier() !== null) {
                    $this->write_optional_space();
                }
            }
            if ($attribute->get_modifier() !== null) {
                $this->buffer->write($attribute->get_modifier());
            }
        }
        $this->buffer->write_char(']');
    }
    public function visit_class_selector(Class_Selector $klass): void
    {
        $this->buffer->write_char('.');
        $this->buffer->write($klass->get_name());
    }
    public function visit_complex_selector(Complex_Selector $complex): void
    {
        $this->write_combinators($complex->get_leading_combinators());
        if (\count($complex->get_leading_combinators()) !== 0 && \count($complex->get_components()) !== 0) {
            $this->write_optional_space();
        }
        foreach ($complex->get_components() as $i => $component) {
            $this->visit_compound_selector($component->get_selector());
            if (\count($component->get_combinators()) !== 0) {
                $this->write_optional_space();
            }
            $this->write_combinators($component->get_combinators());
            if ($i !== \count($complex->get_components()) - 1 && (!$this->compressed || \count($component->get_combinators()) === 0)) {
                $this->buffer->write_char(' ');
            }
        }
    }
    /**
     * Writes $combinators to {@see buffer}, with spaces in between in expanded
     * mode.
     *
     * @param list<CssValue<Combinator>> $combinators
     */
    private function write_combinators(array $combinators): void
    {
        $this->write_between($combinators, $this->compressed ? '' : ' ', function ($text): void {
            $this->buffer->write($text);
        });
    }
    public function visit_compound_selector(Compound_Selector $compound): void
    {
        $start = $this->buffer->get_length();
        foreach ($compound->get_components() as $simple) {
            $simple->accept($this);
        }
        // If we emit an empty compound, it's because all of the components got
        // optimized out because they match all selectors, so we just emit the
        // universal selector.
        if ($this->buffer->get_length() === $start) {
            $this->buffer->write_char('*');
        }
    }
    public function visit_id_selector(Id_Selector $id): void
    {
        $this->buffer->write_char('#');
        $this->buffer->write($id->get_name());
    }
    public function visit_selector_list(Selector_List $list): void
    {
        $first = true;
        foreach ($list->get_components() as $complex) {
            if (!$this->inspect && $complex->is_invisible()) {
                continue;
            }
            if ($first) {
                $first = false;
            } else {
                $this->buffer->write_char(',');
                if ($complex->get_line_break()) {
                    $this->write_line_feed();
                    $this->write_indentation();
                } else {
                    $this->write_optional_space();
                }
            }
            $this->visit_complex_selector($complex);
        }
    }
    public function visit_parent_selector(Parent_Selector $parent): void
    {
        $this->buffer->write_char('&');
        if ($parent->get_suffix() !== null) {
            $this->buffer->write($parent->get_suffix());
        }
    }
    public function visit_placeholder_selector(Placeholder_Selector $placeholder): void
    {
        $this->buffer->write_char('%');
        $this->buffer->write($placeholder->get_name());
    }
    public function visit_pseudo_selector(Pseudo_Selector $pseudo): void
    {
        $inner_selector = $pseudo->get_selector();
        // `:not(%a)` is semantically identical to `*`.
        if ($inner_selector !== null && $pseudo->get_name() === 'not' && $inner_selector->is_invisible()) {
            return;
        }
        $this->buffer->write_char(':');
        if ($pseudo->is_syntactic_element()) {
            $this->buffer->write_char(':');
        }
        $this->buffer->write($pseudo->get_name());
        if ($pseudo->get_argument() === null && $pseudo->get_selector() === null) {
            return;
        }
        $this->buffer->write_char('(');
        if ($pseudo->get_argument() !== null) {
            $this->buffer->write($pseudo->get_argument());
            if ($pseudo->get_selector() !== null) {
                $this->buffer->write_char(' ');
            }
        }
        if ($inner_selector !== null) {
            $this->visit_selector_list($inner_selector);
        }
        $this->buffer->write_char(')');
    }
    public function visit_type_selector(Type_Selector $type): void
    {
        $this->buffer->write($type->get_name());
    }
    public function visit_universal_selector(Universal_Selector $universal): void
    {
        if ($universal->get_namespace() !== null) {
            $this->buffer->write($universal->get_namespace());
            $this->buffer->write_char('|');
        }
        $this->buffer->write_char('*');
    }
    // ## Utilities
    /**
     * Runs $callback and associates all text written within it with the span of $node
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    private function for(Ast_Node $node, callable $callback)
    {
        return $this->buffer->for_span($node->get_span(), $callback);
    }
    /**
     * @param CssValue<string> $value
     */
    private function write(Css_Value $value): void
    {
        $this->for($value, function () use ($value): void {
            $this->buffer->write($value->get_value());
        });
    }
    /**
     * Emits `$parent->getChildren()` in a block
     */
    private function visit_children(Css_Parent_Node $parent): void
    {
        $this->buffer->write_char('{');
        $pre_previous = null;
        $previous = null;
        foreach ($parent->get_children() as $child) {
            if ($this->is_invisible($child)) {
                continue;
            }
            if ($previous !== null && $this->requires_semicolon($previous)) {
                $this->buffer->write_char(';');
            }
            if ($this->is_trailing_comment($child, $previous ?? $parent)) {
                $this->write_optional_space();
                $this->without_indentation(function () use ($child): void {
                    $child->accept($this);
                });
            } else {
                $this->write_line_feed();
                $this->indent(function () use ($child): void {
                    $child->accept($this);
                });
            }
            $pre_previous = $previous;
            $previous = $child;
        }
        if ($previous !== null) {
            if ($this->requires_semicolon($previous) && !$this->compressed) {
                $this->buffer->write_char(';');
            }
            if ($pre_previous === null && $this->is_trailing_comment($previous, $parent)) {
                $this->write_optional_space();
            } else {
                $this->write_line_feed();
                $this->write_indentation();
            }
        }
        $this->buffer->write_char('}');
    }
    /**
     * Whether $node requires a semicolon to be written after it.
     */
    private function requires_semicolon(Css_Node $node): bool
    {
        if ($node instanceof Css_Parent_Node) {
            return $node->is_childless();
        }
        return !$node instanceof Css_Comment;
    }
    private function is_trailing_comment(Css_Node $node, Css_Node $previous): bool
    {
        // Short-circuit in compressed mode to avoid expensive span shenanigans
        // (shespanigans?), since we're compressing all whitespace anyway.
        if ($this->compressed) {
            return false;
        }
        if (!$node instanceof Css_Comment) {
            return false;
        }
        if ($node->get_span()->get_source_url() !== $previous->get_span()->get_source_url()) {
            return false;
        }
        if (!Span_Util::contains($previous->get_span(), $node->get_span())) {
            return $node->get_span()->get_start()->get_line() === $previous->get_span()->get_end()->get_line();
        }
        // Walk back from just before the current node starts looking for the
        // parent's left brace (to open the child block). This is safer than a
        // simple forward search of the previous.span.text as that might contain
        // other left braces.
        $search_from = $node->get_span()->get_start()->get_offset() - $previous->get_span()->get_start()->get_offset() - 1;
        // Imports can cause a node to be "contained" by another node when they are
        // actually the same node twice in a row.
        if ($search_from < 0) {
            return false;
        }
        $previous_span_text = $previous->get_span()->get_text();
        $end_offset = strrpos($previous_span_text, '{', $search_from - \strlen($previous_span_text));
        if ($end_offset === false) {
            $end_offset = 0;
        }
        $span = $previous->get_span()->get_file()->span($previous->get_span()->get_start()->get_offset(), $previous->get_span()->get_start()->get_offset() + $end_offset);
        return $node->get_span()->get_start()->get_line() === $span->get_end()->get_line();
    }
    /**
     * Writes a line feed, unless this emitting compressed CSS.
     */
    private function write_line_feed(): void
    {
        if (!$this->compressed) {
            $this->buffer->write_char("\n");
        }
    }
    private function write_optional_space(): void
    {
        if (!$this->compressed) {
            $this->buffer->write_char(' ');
        }
    }
    private function write_indentation(): void
    {
        if (!$this->compressed) {
            $this->write_times(' ', $this->indentation * 2);
        }
    }
    /**
     * Writes $char to {@see buffer} with $times repetitions.
     */
    private function write_times(string $char, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->buffer->write_char($char);
        }
    }
    /**
     * Calls $callback to write each value in $iterable, and writes $text
     * between each one.
     *
     * @template T
     *
     * @param iterable<T>       $iterable
     * @param callable(T): void $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    private function write_between(iterable $iterable, string $text, callable $callback): void
    {
        $first = true;
        foreach ($iterable as $value) {
            if ($first) {
                $first = false;
            } else {
                $this->buffer->write($text);
            }
            $callback($value);
        }
    }
    /**
     * Returns a comma used to separate values in lists.
     */
    private function get_comma_separator(): string
    {
        return $this->compressed ? ',' : ', ';
    }
    /**
     * Runs $callback with indentation increased one level.
     *
     * @param callable(): void $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    private function indent(callable $callback): void
    {
        $this->indentation++;
        $callback();
        $this->indentation--;
    }
    /**
     * Runs $callback without any indentation.
     *
     * @param callable(): void $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    private function without_indentation(callable $callback): void
    {
        $saved_indentation = $this->indentation;
        $this->indentation = 0;
        $callback();
        $this->indentation = $saved_indentation;
    }
    /**
     * Returns whether $node is invisible.
     */
    private function is_invisible(Css_Node $node): bool
    {
        return !$this->inspect && ($this->compressed ? $node->is_invisible_hiding_comments() : $node->is_invisible());
    }
}