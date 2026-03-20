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
namespace Scss_Php\Scss_Php\Parser;

use League\Uri\Exceptions\Syntax_Error;
use League\Uri\Uri;
use Scss_Php\Scss_Php\Ast\Sass\Argument;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operator;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Boolean_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Color_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\If_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Interpolated_Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\List_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Map_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Null_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Number_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Parenthesized_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Selector_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Supports_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operator;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Variable_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Import;
use Scss_Php\Scss_Php\Ast\Sass\Import\Dynamic_Import;
use Scss_Php\Scss_Php\Ast\Sass\Import\Static_Import;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Block;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Debug_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Each_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Else_Clause;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Error_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\For_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Function_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Clause;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Import_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Include_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Media_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Mixin_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Return_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Style_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Supports_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Variable_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Warn_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\While_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Anything;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Function;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Negation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Operation;
use Scss_Php\Scss_Php\Colors;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Logger_Util;
use Scss_Php\Scss_Php\Util\Path;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Span_Color_Format;
use Source_Span\File_Span;
/**
 * @internal
 */
abstract class Stylesheet_Parser extends Parser
{
    /**
     * The silent comment this parser encountered previously.
     */
    protected ?Silent_Comment $last_silent_comment = null;
    /**
     * Whether we've consumed a rule other than `@charset`, `@forward`, or `@use`.
     */
    private bool $is_use_allowed = true;
    /**
     * Whether the parser is currently parsing the contents of a mixin declaration.
     */
    private bool $in_mixin = false;
    /**
     * Whether the parser is currently parsing a content block passed to a mixin.
     */
    private bool $in_content_block = false;
    /**
     * Whether the parser is currently parsing a control directive such as `@if`
     * or `@each`.
     */
    private bool $in_control_directive = false;
    /**
     * Whether the parser is currently parsing an unknown rule.
     */
    private bool $in_unknown_at_rule = false;
    /**
     * Whether the parser is currently parsing a style rule.
     */
    private bool $in_style_rule = false;
    /**
     * Whether the parser is currently within a parenthesized expression.
     */
    private bool $in_parentheses = false;
    /**
     * Whether the parser is currently within an expression.
     */
    private bool $in_expression = false;
    /**
     * A map from all variable names that are assigned with `!global` in the
     * current stylesheet to the nodes where they're defined.
     *
     * These are collected at parse time because they affect the variables
     * exposed by the module generated for this stylesheet, *even if they aren't
     * evaluated*. This allows us to ensure that the stylesheet always exposes
     * the same set of variable names no matter how it's evaluated.
     *
     * @var array<string, VariableDeclaration>
     */
    private array $global_variables = [];
    protected function in_expression(): bool
    {
        return $this->in_expression;
    }
    /**
     * @throws SassFormatException when parsing fails
     */
    public function parse(): Stylesheet
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet {
            $start = $this->scanner->get_position();
            // Allow a byte-order mark at the beginning of the document.
            $this->scanner->scan("﻿");
            $statements = $this->statements(function (): ?\Scss_Php\Scss_Php\Ast\Sass\Statement {
                // Handle this specially so that {@see atRule} always returns a non-nullable Statement.
                if ($this->scanner->scan('@charset')) {
                    $this->whitespace();
                    $this->string();
                    return null;
                }
                return $this->statement(true);
            });
            $this->scanner->expect_done();
            // Ensure that all global variable assignments produce a variable in this
            // stylesheet, even if they aren't evaluated. See sass/language#50.
            foreach ($this->global_variables as $declaration) {
                $statements[] = new Variable_Declaration($declaration->get_name(), new Null_Expression($declaration->get_expression()->get_span()), $declaration->get_span(), null, true);
            }
            return new Stylesheet($statements, $this->scanner->span_from($start), $this->is_plain_css());
        });
    }
    public function parse_argument_declaration(): Argument_Declaration
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration {
            $this->scanner->expect_char('@', '@-rule');
            $this->identifier();
            $this->whitespace();
            $this->identifier();
            $arguments = $this->argument_declaration();
            $this->whitespace();
            $this->scanner->expect_char('{');
            $this->scanner->expect_done();
            return $arguments;
        });
    }
    /**
     * Consumes a statement that's allowed at the top level of the stylesheet or
     * within nested style and at rules.
     *
     * If $root is `true`, this parses at-rules that are allowed only at the
     * root of the stylesheet.
     */
    private function statement(bool $root = false): Statement
    {
        switch ($this->scanner->peek_char()) {
            case '@':
                return $this->at_rule($this->statement(...), $root);
            case '+':
                if (!$this->is_indented() || !$this->looking_at_identifier(1)) {
                    return $this->style_rule();
                }
                $this->is_use_allowed = false;
                $start = $this->scanner->get_position();
                $this->scanner->read_char();
                return $this->include_rule($start);
            case '=':
                if (!$this->is_indented()) {
                    return $this->style_rule();
                }
                $this->is_use_allowed = false;
                $start = $this->scanner->get_position();
                $this->scanner->read_char();
                $this->whitespace();
                return $this->mixin_rule($start);
            case '}':
                $this->scanner->error('unmatched "}".');
            // no break
            default:
                if ($this->in_style_rule || $this->in_unknown_at_rule || $this->in_mixin || $this->in_content_block) {
                    return $this->declaration_or_style_rule();
                }
                return $this->variable_declaration_or_style_rule();
        }
    }
    /**
     * Consumes a namespaced variable declaration.
     *
     * @throws FormatException
     */
    private function variable_declaration_with_namespace(): Variable_Declaration
    {
        $start = $this->scanner->get_position();
        $namespace = $this->identifier();
        $this->scanner->expect_char('.');
        return $this->variable_declaration_without_namespace($namespace, $start);
    }
    /**
     * Consumes a variable declaration.
     */
    protected function variable_declaration_without_namespace(?string $namespace = null, ?int $start = null): Variable_Declaration
    {
        $preceding_comment = $this->last_silent_comment;
        $this->last_silent_comment = null;
        $start ??= $this->scanner->get_position();
        $name = $this->variable_name();
        if ($namespace !== null) {
            $this->assert_public($name, fn(): \Source_Span\File_Span => $this->scanner->span_from($start));
        }
        if ($this->is_plain_css()) {
            $this->error('Sass variables aren\'t allowed in plain CSS.', $this->scanner->span_from($start));
        }
        $this->whitespace();
        $this->scanner->expect_char(':');
        $this->whitespace();
        $value = $this->expression();
        $guarded = false;
        $global = false;
        $flag_start = $this->scanner->get_position();
        while ($this->scanner->scan_char('!')) {
            $flag = $this->identifier();
            if ($flag === 'default') {
                if ($guarded) {
                    Logger_Util::warn_for_deprecation($this->logger, Deprecation::duplicateVarFlags, "!default should only be written once for each variable.\nThis will be an error in Dart Sass 2.0.0.", $this->scanner->span_from($flag_start));
                }
                $guarded = true;
            } elseif ($flag === 'global') {
                if ($namespace !== null) {
                    $this->error("!global isn't allowed for variables in other modules.", $this->scanner->span_from($flag_start));
                } elseif ($global) {
                    Logger_Util::warn_for_deprecation($this->logger, Deprecation::duplicateVarFlags, "!global should only be written once for each variable.\nThis will be an error in Dart Sass 2.0.0.", $this->scanner->span_from($flag_start));
                }
                $global = true;
            } else {
                $this->error('Invalid flag name.', $this->scanner->span_from($flag_start));
            }
            $this->whitespace();
            $flag_start = $this->scanner->get_position();
        }
        $this->expect_statement_separator('variable declaration');
        // TODO remove this when implementing modules
        if ($namespace !== null) {
            $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
        }
        $declaration = new Variable_Declaration($name, $value, $this->scanner->span_from($start), $namespace, $guarded, $global, $preceding_comment);
        if ($global && !isset($this->global_variables[$name])) {
            $this->global_variables[$name] = $declaration;
        }
        return $declaration;
    }
    private function variable_declaration_or_style_rule(): Statement
    {
        if ($this->is_plain_css()) {
            return $this->style_rule();
        }
        // The indented syntax allows a single backslash to distinguish a style rule
        // from old-style property syntax. We don't support old property syntax, but
        // we do support the backslash because it's easy to do.
        if ($this->is_indented() && $this->scanner->scan_char('\\')) {
            return $this->style_rule();
        }
        if (!$this->looking_at_identifier()) {
            return $this->style_rule();
        }
        $start = $this->scanner->get_position();
        $variable_or_interpolation = $this->variable_declaration_or_interpolation();
        if ($variable_or_interpolation instanceof Variable_Declaration) {
            return $variable_or_interpolation;
        }
        $buffer = new Interpolation_Buffer();
        $buffer->add_interpolation($variable_or_interpolation);
        return $this->style_rule($buffer, $start);
    }
    /**
     * Consumes a {@see VariableDeclaration}, a {@see Declaration}, or a {@see StyleRule}.
     *
     * @throws FormatException
     */
    private function declaration_or_style_rule(): Statement
    {
        // The indented syntax allows a single backslash to distinguish a style rule
        // from old-style property syntax. We don't support old property syntax, but
        // we do support the backslash because it's easy to do.
        if ($this->is_indented() && $this->scanner->scan_char('\\')) {
            return $this->style_rule();
        }
        $start = $this->scanner->get_position();
        $declaration_buffer = $this->declaration_or_buffer();
        if ($declaration_buffer instanceof Statement) {
            return $declaration_buffer;
        }
        return $this->style_rule($declaration_buffer, $start);
    }
    /**
     * Tries to parse a variable or property declaration, and returns the value
     * parsed so far if it fails.
     *
     * This can return either an {@see InterpolationBuffer}, indicating that it
     * couldn't consume a declaration and that selector parsing should be
     * attempted; or it can return a {@see Declaration} or a {@see VariableDeclaration},
     * indicating that it successfully consumed a declaration.
     */
    private function declaration_or_buffer(): Statement|Interpolation_Buffer
    {
        $start = $this->scanner->get_position();
        $name_buffer = new Interpolation_Buffer();
        $first = $this->scanner->peek_char();
        $starts_with_punctuation = false;
        // Allow the "*prop: val", ":prop: val", "#prop: val", and ".prop: val"
        // hacks.
        if ($first === ':' || $first === '*' || $first === '.' || $first === '#' && $this->scanner->peek_char(1) !== '{') {
            $starts_with_punctuation = true;
            $name_buffer->write($this->scanner->read_char());
            $name_buffer->write($this->raw_text($this->whitespace(...)));
        }
        if (!$this->looking_at_interpolated_identifier()) {
            return $name_buffer;
        }
        $variable_or_interpolation = $starts_with_punctuation ? $this->interpolated_identifier() : $this->variable_declaration_or_interpolation();
        if ($variable_or_interpolation instanceof Variable_Declaration) {
            return $variable_or_interpolation;
        }
        $name_buffer->add_interpolation($variable_or_interpolation);
        $this->is_use_allowed = false;
        if ($this->scanner->matches('/*')) {
            $name_buffer->write($this->raw_text($this->loud_comment(...)));
        }
        $mid_buffer = $this->raw_text($this->whitespace(...));
        $before_colon = $this->scanner->get_position();
        if (!$this->scanner->scan_char(':')) {
            if ($mid_buffer !== '') {
                $name_buffer->write(' ');
            }
            return $name_buffer;
        }
        $mid_buffer .= ':';
        // Parse custom properties as declarations no matter what.
        $name = $name_buffer->build_interpolation($this->scanner->span_from($start, $before_colon));
        if (str_starts_with($name->get_initial_plain(), '--')) {
            $value = new String_Expression($this->interpolated_declaration_value(silentComments: false));
            $this->expect_statement_separator('custom property');
            return Declaration::create($name, $value, $this->scanner->span_from($start));
        }
        if ($this->scanner->scan_char(':')) {
            $name_buffer->write($mid_buffer);
            $name_buffer->write(':');
            return $name_buffer;
        }
        if ($this->is_indented() && $this->looking_at_interpolated_identifier()) {
            // In the indented syntax, `foo:bar` is always considered a selector
            // rather than a property.
            $name_buffer->write($mid_buffer);
            return $name_buffer;
        }
        $post_colon_whitespace = $this->raw_text($this->whitespace(...));
        $nested = $this->try_declaration_children($name, $start);
        if ($nested !== null) {
            return $nested;
        }
        $mid_buffer .= $post_colon_whitespace;
        $could_be_selector = $post_colon_whitespace === '' && $this->looking_at_interpolated_identifier();
        $before_declaration = $this->scanner->get_position();
        try {
            $value = $this->expression();
            if ($this->looking_at_children()) {
                // Properties that are ambiguous with selectors can't have additional
                // properties nested beneath them, so we force an error. This will be
                // caught below and cause the text to be reparsed as a selector.
                if ($could_be_selector) {
                    $this->expect_statement_separator();
                }
            } elseif (!$this->at_end_of_statement()) {
                // Force an exception if there isn't a valid end-of-property character
                // but don't consume that character. This will also cause the text to be
                // reparsed.
                $this->expect_statement_separator();
            }
        } catch (Format_Exception $e) {
            if (!$could_be_selector) {
                throw $e;
            }
            // If the value would be followed by a semicolon, it's definitely supposed
            // to be a property, not a selector.
            $this->scanner->set_position($before_declaration);
            $additional = $this->almost_any_value();
            if (!$this->is_indented() && $this->scanner->peek_char() === ';') {
                throw $e;
            }
            $name_buffer->write($mid_buffer);
            $name_buffer->add_interpolation($additional);
            return $name_buffer;
        }
        $nested = $this->try_declaration_children($name, $start, $value);
        if ($nested !== null) {
            return $nested;
        }
        $this->expect_statement_separator();
        return Declaration::create($name, $value, $this->scanner->span_from($start));
    }
    /**
     * Tries to parse a namespaced {@see VariableDeclaration}, and returns the value
     * parsed so far if it fails.
     *
     * This can return either an {@see Interpolation}, indicating that it couldn't
     * consume a variable declaration and that property declaration or selector
     * parsing should be attempted; or it can return a {@see VariableDeclaration},
     * indicating that it successfully consumed a variable declaration.
     */
    private function variable_declaration_or_interpolation(): Interpolation|Variable_Declaration
    {
        if (!$this->looking_at_identifier()) {
            return $this->interpolated_identifier();
        }
        $start = $this->scanner->get_position();
        $identifier = $this->identifier();
        if ($this->scanner->matches('.$')) {
            $this->scanner->read_char();
            return $this->variable_declaration_without_namespace($identifier, $start);
        }
        $buffer = new Interpolation_Buffer();
        $buffer->write($identifier);
        // Parse the rest of an interpolated identifier if one exists, so callers
        // don't have to.
        if ($this->looking_at_interpolated_identifier_body()) {
            $buffer->add_interpolation($this->interpolated_identifier());
        }
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes a StyleRule
     */
    private function style_rule(?Interpolation_Buffer $buffer = null, ?int $start = null): Style_Rule
    {
        $start ??= $this->scanner->get_position();
        $interpolation = $this->style_rule_selector();
        if ($buffer !== null) {
            $buffer->add_interpolation($interpolation);
            $interpolation = $buffer->build_interpolation($this->scanner->span_from($start));
        }
        if (!$interpolation->get_contents()) {
            $this->scanner->error('expected "}".');
        }
        $was_in_style_rule = $this->in_style_rule;
        $this->in_style_rule = true;
        return $this->with_children($this->statement(...), $start, function (array $children) use ($was_in_style_rule, $start, $interpolation): \Scss_Php\Scss_Php\Ast\Sass\Statement\Style_Rule {
            if ($this->is_indented() && $children === []) {
                $this->warn("This selector doesn't have any properties and won't be rendered.", $interpolation->get_span());
            }
            $this->in_style_rule = $was_in_style_rule;
            return new Style_Rule($interpolation, $children, $this->scanner->span_from($start));
        });
    }
    /**
     * Consumes either a property declaration or a namespaced variable declaration.
     *
     * This is only used in contexts where declarations are allowed but style
     * rules are not, such as nested declarations. Otherwise,
     * {@see declarationOrStyleRule} is used instead.
     *
     * If $parseCustomProperties is `true`, properties that begin with `--` will
     * be parsed using custom property parsing rules.
     */
    private function property_or_variable_declaration(bool $parse_custom_properties = true): Statement
    {
        $start = $this->scanner->get_position();
        // Allow the "*prop: val", ":prop: val", "#prop: val", and ".prop: val"
        // hacks.
        $first = $this->scanner->peek_char();
        if ($first === ':' || $first === '*' || $first === '.' || $first === '#' && $this->scanner->peek_char(1) !== '{') {
            $name_buffer = new Interpolation_Buffer();
            $name_buffer->write($this->scanner->read_char());
            $name_buffer->write($this->raw_text($this->whitespace(...)));
            $name_buffer->add_interpolation($this->interpolated_identifier());
            $name = $name_buffer->build_interpolation($this->scanner->span_from($start));
        } elseif (!$this->is_plain_css()) {
            $variable_or_interpolation = $this->variable_declaration_or_interpolation();
            if ($variable_or_interpolation instanceof Variable_Declaration) {
                return $variable_or_interpolation;
            }
            $name = $variable_or_interpolation;
        } else {
            $name = $this->interpolated_identifier();
        }
        $this->whitespace();
        $this->scanner->expect_char(':');
        if ($parse_custom_properties && str_starts_with($name->get_initial_plain(), '--')) {
            $value = new String_Expression($this->interpolated_declaration_value(silentComments: false));
            $this->expect_statement_separator('custom property');
            return Declaration::create($name, $value, $this->scanner->span_from($start));
        }
        $this->whitespace();
        $nested = $this->try_declaration_children($name, $start);
        if ($nested !== null) {
            return $nested;
        }
        $value = $this->expression();
        $nested = $this->try_declaration_children($name, $start, $value);
        if ($nested !== null) {
            return $nested;
        }
        $this->expect_statement_separator();
        return Declaration::create($name, $value, $this->scanner->span_from($start));
    }
    /**
     * Tries parsing nested children of a declaration whose $name has already
     * been parsed, and returns `null` if it doesn't have any.
     *
     * If $value is passed, it's used as the value of the property without
     * nesting.
     */
    private function try_declaration_children(Interpolation $name, int $start, ?Expression $value = null): ?Declaration
    {
        if (!$this->looking_at_children()) {
            return null;
        }
        if ($this->is_plain_css()) {
            $this->scanner->error("Nested declarations aren't allowed in plain CSS.");
        }
        return $this->with_children($this->declaration_child(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\Declaration => Declaration::nested($name, $children, $span, $value));
    }
    /**
     * Consumes a statement that's allowed within a declaration.
     */
    private function declaration_child(): Statement
    {
        if ($this->scanner->peek_char() === '@') {
            return $this->declaration_at_rule();
        }
        return $this->property_or_variable_declaration(false);
    }
    /**
     * Consumes an at-rule.
     *
     * This consumes at-rules that are allowed at all levels of the document; the
     * $child parameter is called to consume any at-rules that are specifically
     * allowed in the caller's context.
     *
     * If $root is `true`, this parses at-rules that are allowed only at the
     * root of the stylesheet.
     *
     * @param callable(): Statement $child
     *
     * @param-immediately-invoked-callable $child
     */
    protected function at_rule(callable $child, bool $root = false): Statement
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('@', '@-rule');
        $name = $this->interpolated_identifier();
        $this->whitespace();
        $was_use_allowed = $this->is_use_allowed;
        $this->is_use_allowed = false;
        switch ($name->get_as_plain()) {
            case 'at-root':
                return $this->at_root_rule($start);
            case 'content':
                return $this->content_rule($start);
            case 'debug':
                return $this->debug_rule($start);
            case 'each':
                return $this->each_rule($start, $child);
            case 'else':
                $this->disallowed_at_rule($start);
            // no break
            case 'error':
                return $this->error_rule($start);
            case 'extend':
                return $this->extend_rule($start);
            case 'for':
                return $this->for_rule($start, $child);
            case 'forward':
                $this->is_use_allowed = $was_use_allowed;
                if (!$root) {
                    $this->disallowed_at_rule($start);
                }
                // TODO remove this when implementing modules
                $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
            // no break
            case 'function':
                return $this->function_rule($start);
            case 'if':
                return $this->if_rule($start, $child);
            case 'import':
                return $this->import_rule($start);
            case 'include':
                return $this->include_rule($start);
            case 'media':
                return $this->media_rule($start);
            case 'mixin':
                return $this->mixin_rule($start);
            case '-moz-document':
                return $this->moz_document_rule($start, $name);
            case 'return':
                $this->disallowed_at_rule($start);
            // no break
            case 'supports':
                return $this->supports_rule($start);
            case 'use':
                $this->is_use_allowed = $was_use_allowed;
                if (!$root) {
                    $this->disallowed_at_rule($start);
                }
                // TODO remove this when implementing modules
                $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
            // no break
            case 'warn':
                return $this->warn_rule($start);
            case 'while':
                return $this->while_rule($start, $child);
            default:
                return $this->unknown_at_rule($start, $name);
        }
    }
    /**
     * Consumes an at-rule allowed within a property declaration.
     */
    private function declaration_at_rule(): Statement
    {
        $start = $this->scanner->get_position();
        $name = $this->plain_at_rule_name();
        switch ($name) {
            case 'content':
                return $this->content_rule($start);
            case 'debug':
                return $this->debug_rule($start);
            case 'each':
                return $this->each_rule($start, $this->declaration_child(...));
            case 'else':
                $this->disallowed_at_rule($start);
            // no break
            case 'error':
                return $this->error_rule($start);
            case 'for':
                return $this->for_rule($start, $this->declaration_child(...));
            case 'if':
                return $this->if_rule($start, $this->declaration_child(...));
            case 'include':
                return $this->include_rule($start);
            case 'warn':
                return $this->warn_rule($start);
            case 'while':
                return $this->while_rule($start, $this->declaration_child(...));
            default:
                $this->disallowed_at_rule($start);
        }
    }
    /**
     * Consumes a statement allowed within a function.
     */
    private function function_child(): Statement
    {
        if ($this->scanner->peek_char() !== '@') {
            $start = $this->scanner->get_position();
            try {
                return $this->variable_declaration_with_namespace();
            } catch (Format_Exception $variable_declaration_error) {
                // TODO remove this when implementing modules
                if ($variable_declaration_error->get_message() === 'Sass modules are not implemented yet.') {
                    throw $variable_declaration_error;
                }
                $this->scanner->set_position($start);
                // If a variable declaration failed to parse, it's possible the user
                // thought they could write a style rule or property declaration in a
                // function. If so, throw a more helpful error message.
                try {
                    $statement = $this->declaration_or_style_rule();
                } catch (Format_Exception) {
                    throw $variable_declaration_error;
                }
                $this->error('@function rules may not contain ' . ($statement instanceof Style_Rule ? 'style rules.' : 'declarations.'), $statement->get_span());
            }
        }
        $start = $this->scanner->get_position();
        switch ($this->plain_at_rule_name()) {
            case 'debug':
                return $this->debug_rule($start);
            case 'each':
                return $this->each_rule($start, $this->function_child(...));
            case 'else':
                $this->disallowed_at_rule($start);
            // no break
            case 'error':
                return $this->error_rule($start);
            case 'for':
                return $this->for_rule($start, $this->function_child(...));
            case 'if':
                return $this->if_rule($start, $this->function_child(...));
            case 'return':
                return $this->return_rule($start);
            case 'warn':
                return $this->warn_rule($start);
            case 'while':
                return $this->while_rule($start, $this->function_child(...));
            default:
                $this->disallowed_at_rule($start);
        }
    }
    /**
     * Consumes an at-rule's name, with interpolation disallowed.
     */
    private function plain_at_rule_name(): string
    {
        $this->scanner->expect_char('@', '@-rule');
        $name = $this->identifier();
        $this->whitespace();
        return $name;
    }
    /**
     * Consumes an `@at-root` rule.
     *
     * $start should point before the `@`.
     */
    private function at_root_rule(int $start): At_Root_Rule
    {
        if ($this->scanner->peek_char() === '(') {
            $query = $this->at_root_query();
            $this->whitespace();
            return $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule => new At_Root_Rule($children, $span, $query));
        }
        if ($this->looking_at_children() || $this->is_indented() && $this->at_end_of_statement()) {
            return $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule => new At_Root_Rule($children, $span));
        }
        $child = $this->style_rule();
        return new At_Root_Rule([$child], $this->scanner->span_from($start));
    }
    /**
     * Consumes a query expression of the form `(foo: bar)`.
     */
    private function at_root_query(): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        $this->scanner->expect_char('(');
        $buffer->write('(');
        $this->whitespace();
        $this->add_or_inject($buffer, $this->expression());
        if ($this->scanner->scan_char(':')) {
            $this->whitespace();
            $buffer->write(': ');
            $this->add_or_inject($buffer, $this->expression());
        }
        $this->scanner->expect_char(')');
        $this->whitespace();
        $buffer->write(')');
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes a `@content` rule.
     *
     * $start should point before the `@`.
     */
    private function content_rule(int $start): Content_Rule
    {
        if (!$this->in_mixin) {
            $this->error('@content is only allowed within mixin declarations.', $this->scanner->span_from($start));
        }
        $before_whitespace = $this->scanner->get_location();
        $this->whitespace();
        if ($this->scanner->peek_char() === '(') {
            $arguments = $this->argument_invocation(true);
            $this->whitespace();
        } else {
            $arguments = Argument_Invocation::create_empty($before_whitespace->point_span());
        }
        $this->expect_statement_separator('@content rule');
        return new Content_Rule($arguments, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@debug` rule.
     *
     * $start should point before the `@`.
     */
    private function debug_rule(int $start): Debug_Rule
    {
        $value = $this->expression();
        $this->expect_statement_separator('@debug rule');
        return new Debug_Rule($value, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@each` rule.
     *
     * $start should point before the `@`. $child is called to consume any
     * children that are specifically allowed in the caller's context.
     *
     * @param callable(): Statement $child
     *
     * @param-immediately-invoked-callable $child
     */
    private function each_rule(int $start, callable $child): Each_Rule
    {
        $was_in_control_directive = $this->in_control_directive;
        $this->in_control_directive = true;
        $variables = [$this->variable_name()];
        $this->whitespace();
        while ($this->scanner->scan_char(',')) {
            $this->whitespace();
            $variables[] = $this->variable_name();
            $this->whitespace();
        }
        $this->expect_identifier('in');
        $this->whitespace();
        $list = $this->expression();
        return $this->with_children($child, $start, function (array $children, File_Span $span) use ($variables, $was_in_control_directive, $list): \Scss_Php\Scss_Php\Ast\Sass\Statement\Each_Rule {
            $this->in_control_directive = $was_in_control_directive;
            return new Each_Rule($variables, $list, $children, $span);
        });
    }
    /**
     * Consumes a `@error` rule.
     *
     * $start should point before the `@`.
     */
    private function error_rule(int $start): Error_Rule
    {
        $value = $this->expression();
        $this->expect_statement_separator('@error rule');
        return new Error_Rule($value, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@extend` rule.
     *
     * $start should point before the `@`.
     */
    private function extend_rule(int $start): Extend_Rule
    {
        if (!$this->in_style_rule && !$this->in_mixin && !$this->in_content_block) {
            $this->error('@extend may only be used within style rules.', $this->scanner->span_from($start));
        }
        $value = $this->almost_any_value();
        $optional = $this->scanner->scan_char('!');
        if ($optional) {
            $this->expect_identifier('optional');
            $this->whitespace();
        }
        $this->expect_statement_separator('@extend rule');
        return new Extend_Rule($value, $this->scanner->span_from($start), $optional);
    }
    /**
     * Consumes a function declaration.
     *
     * $start should point before the `@`.
     */
    private function function_rule(int $start): Function_Rule
    {
        $preceding_comment = $this->last_silent_comment;
        $this->last_silent_comment = null;
        $before_name = $this->scanner->get_position();
        $name = $this->identifier();
        if (str_starts_with($name, '--')) {
            Logger_Util::warn_for_deprecation($this->logger, Deprecation::cssFunctionMixin, "Sass @function names beginning with -- are deprecated for forward-compatibility with plain CSS mixins.\n\nFor details, see https://sass-lang.com/d/css-function-mixin", $this->scanner->span_from($before_name));
        }
        $this->whitespace();
        $arguments = $this->argument_declaration();
        if ($this->in_mixin || $this->in_content_block) {
            $this->error('Mixins may not contain function declarations.', $this->scanner->span_from($start));
        }
        if ($this->in_control_directive) {
            $this->error('Functions may not be declared in control directives.', $this->scanner->span_from($start));
        }
        switch (Util::unvendor($name)) {
            case 'calc':
            case 'element':
            case 'expression':
            case 'url':
            case 'and':
            case 'or':
            case 'not':
            case 'clamp':
                $this->error('Invalid function name.', $this->scanner->span_from($start));
        }
        $this->whitespace();
        return $this->with_children($this->function_child(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\Function_Rule => new Function_Rule($name, $arguments, $span, $children, $preceding_comment));
    }
    /**
     * Consumes a `@for` rule.
     *
     * $start should point before the `@`. $child is called to consume any
     * children that are specifically allowed in the caller's context.
     *
     * @param callable(): Statement $child
     *
     * @param-immediately-invoked-callable $child
     */
    private function for_rule(int $start, callable $child): For_Rule
    {
        $was_in_control_directive = $this->in_control_directive;
        $this->in_control_directive = true;
        $variable = $this->variable_name();
        $this->whitespace();
        $this->expect_identifier('from');
        $this->whitespace();
        $exclusive = null;
        $from = $this->expression(function () use (&$exclusive): bool {
            if (!$this->looking_at_identifier()) {
                return false;
            }
            if ($this->scan_identifier('to')) {
                $exclusive = true;
                return true;
            }
            if ($this->scan_identifier('through')) {
                $exclusive = false;
                return true;
            }
            return false;
        });
        if ($exclusive === null) {
            $this->scanner->error('Expected "to" or "through".');
        }
        $this->whitespace();
        $to = $this->expression();
        return $this->with_children($child, $start, function (array $children, File_Span $span) use ($variable, $from, $to, $exclusive, $was_in_control_directive): \Scss_Php\Scss_Php\Ast\Sass\Statement\For_Rule {
            $this->in_control_directive = $was_in_control_directive;
            return new For_Rule($variable, $from, $to, $children, $span, $exclusive);
        });
    }
    /**
     * Consumes a `@if` rule.
     *
     * $start should point before the `@`. $child is called to consume any
     * children that are specifically allowed in the caller's context.
     *
     * @param callable(): Statement $child
     *
     * @param-immediately-invoked-callable $child
     */
    private function if_rule(int $start, callable $child): If_Rule
    {
        $if_indentation = $this->get_current_indentation();
        $was_in_control_directive = $this->in_control_directive;
        $this->in_control_directive = true;
        $condition = $this->expression();
        $children = $this->children($child);
        $this->whitespace_without_comments();
        $clauses = [new If_Clause($condition, $children)];
        $last_clause = null;
        while ($this->scan_else($if_indentation)) {
            $this->whitespace();
            if ($this->scan_identifier('if')) {
                $this->whitespace();
                $clauses[] = new If_Clause($this->expression(), $this->children($child));
            } else {
                $last_clause = new Else_Clause($this->children($child));
                break;
            }
        }
        $this->in_control_directive = $was_in_control_directive;
        $span = $this->scanner->span_from($start);
        $this->whitespace_without_comments();
        return new If_Rule($clauses, $span, $last_clause);
    }
    /**
     * Consumes an `@import` rule.
     *
     * $start should point before the `@`.
     */
    private function import_rule(int $start): Import_Rule
    {
        $imports = [];
        do {
            $this->whitespace();
            $argument = $this->import_argument();
            if (($this->in_control_directive || $this->in_mixin) && $argument instanceof Dynamic_Import) {
                $this->disallowed_at_rule($start);
            }
            $imports[] = $argument;
            $this->whitespace();
        } while ($this->scanner->scan_char(','));
        $this->expect_statement_separator('@import rule');
        return new Import_Rule($imports, $this->scanner->span_from($start));
    }
    /**
     * Consumes an argument to an `@import` rule.
     */
    protected function import_argument(): Import
    {
        $start = $this->scanner->get_position();
        $next = $this->scanner->peek_char();
        if ($next === 'u' || $next === 'U') {
            $url = $this->dynamic_url();
            $this->whitespace();
            $modifiers = $this->try_import_modifiers();
            return new Static_Import(new Interpolation([$url], $this->scanner->span_from($start)), $this->scanner->span_from($start), $modifiers);
        }
        $url = $this->string();
        $url_span = $this->scanner->span_from($start);
        $this->whitespace();
        $modifiers = $this->try_import_modifiers();
        if ($this->is_plain_import_url($url) || $modifiers !== null) {
            return new Static_Import(new Interpolation([$url_span->get_text()], $url_span), $this->scanner->span_from($start), $modifiers);
        }
        try {
            return new Dynamic_Import($this->parse_import_url($url), $url_span);
        } catch (Syntax_Error $e) {
            $this->error('Invalid URL: ' . $e->get_message(), $url_span, $e);
        }
    }
    /**
     * Parses $url as an import URL.
     *
     * @throws SyntaxError
     */
    protected function parse_import_url(string $url): string
    {
        // Backwards-compatibility for implementations that allow absolute Windows
        // paths in imports.
        if (Path::is_windows_absolute($url) && !self::is_root_relative_url($url)) {
            return (string) Uri::from_windows_path($url);
        }
        Uri::new($url);
        return $url;
    }
    private static function is_root_relative_url(string $path): bool
    {
        return $path !== '' && $path[0] === '/';
    }
    /**
     * Returns whether $url indicates that an `@import` is a plain CSS import.
     */
    protected function is_plain_import_url(string $url): bool
    {
        if (\strlen($url) < 5) {
            return false;
        }
        if (str_ends_with($url, '.css')) {
            return true;
        }
        if ($url[0] === '/') {
            return $url[1] === '/';
        }
        if ($url[0] !== 'h') {
            return false;
        }
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }
    /**
     * Returns `null` if there are no modifiers.
     */
    protected function try_import_modifiers(): ?Interpolation
    {
        // Exit before allocating anything if we're not looking at any modifiers, as
        // is the most common case.
        if (!$this->looking_at_interpolated_identifier() && $this->scanner->peek_char() !== '(') {
            return null;
        }
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        while (true) {
            if ($this->looking_at_interpolated_identifier()) {
                if (!$buffer->is_empty()) {
                    $buffer->write(' ');
                }
                $identifier = $this->interpolated_identifier();
                $buffer->add_interpolation($identifier);
                $name = $identifier->get_as_plain() !== null ? strtolower($identifier->get_as_plain()) : null;
                if ($name !== 'and' && $this->scanner->scan_char('(')) {
                    if ($name === 'supports') {
                        $query = $this->import_supports_query();
                        if (!$query instanceof Supports_Declaration) {
                            $buffer->write('(');
                        }
                        $buffer->add(new Supports_Expression($query));
                        if (!$query instanceof Supports_Declaration) {
                            $buffer->write(')');
                        }
                    } else {
                        $buffer->write('(');
                        $buffer->add_interpolation($this->interpolated_declaration_value(true, true));
                        $buffer->write(')');
                    }
                    $this->scanner->expect_char(')');
                    $this->whitespace();
                } else {
                    $this->whitespace();
                    if ($this->scanner->scan_char(',')) {
                        $buffer->write(', ');
                        $buffer->add_interpolation($this->media_query_list());
                        return $buffer->build_interpolation($this->scanner->span_from($start));
                    }
                }
            } elseif ($this->scanner->peek_char() === '(') {
                if (!$buffer->is_empty()) {
                    $buffer->write(' ');
                }
                $buffer->add_interpolation($this->media_query_list());
                return $buffer->build_interpolation($this->scanner->span_from($start));
            } else {
                return $buffer->build_interpolation($this->scanner->span_from($start));
            }
        }
    }
    /**
     * Consumes the contents of a `supports()` function after an `@import` rule
     * (but not the function name or parentheses).
     */
    private function import_supports_query(): Supports_Condition
    {
        if ($this->scan_identifier('not')) {
            $this->whitespace();
            $start = $this->scanner->get_position();
            return new Supports_Negation($this->supports_condition_in_parens(), $this->scanner->span_from($start));
        }
        if ($this->scanner->peek_char() === '(') {
            return $this->supports_condition();
        }
        $function = $this->try_import_supports_function();
        if ($function !== null) {
            return $function;
        }
        $start = $this->scanner->get_position();
        $name = $this->expression();
        $this->scanner->expect_char(':');
        return $this->supports_declaration_value($name, $start);
    }
    /**
     * Consumes a function call within a `supports()` function after an
     * `@import` if available.
     */
    private function try_import_supports_function(): ?Supports_Condition
    {
        if (!$this->looking_at_interpolated_identifier()) {
            return null;
        }
        $start = $this->scanner->get_position();
        $name = $this->interpolated_identifier();
        assert($name->get_as_plain() !== 'not');
        if (!$this->scanner->scan_char('(')) {
            $this->scanner->set_position($start);
            return null;
        }
        $value = $this->interpolated_declaration_value(true, true);
        $this->scanner->expect_char(')');
        return new Supports_Function($name, $value, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@include` rule.
     *
     * $start should point before the `@`.
     */
    private function include_rule(int $start): Include_Rule
    {
        $namespace = null;
        $name = $this->identifier();
        if ($this->scanner->scan_char('.')) {
            $namespace = $name;
            $name = $this->public_identifier();
        }
        $this->whitespace();
        $arguments = $this->scanner->peek_char() === '(' ? $this->argument_invocation(true) : Argument_Invocation::create_empty($this->scanner->get_empty_span());
        $this->whitespace();
        $content_arguments = null;
        if ($this->scan_identifier('using')) {
            $this->whitespace();
            $content_arguments = $this->argument_declaration();
            $this->whitespace();
        }
        $content = null;
        if ($content_arguments !== null || $this->looking_at_children()) {
            $content_arguments ??= Argument_Declaration::create_empty($this->scanner->get_empty_span());
            $was_in_content_block = $this->in_content_block;
            $this->in_content_block = true;
            $content = $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Block => new Content_Block($content_arguments, $children, $span));
            $this->in_content_block = $was_in_content_block;
        } else {
            $this->expect_statement_separator();
        }
        $span = $this->scanner->span_from($start, $start)->expand(($content ?? $arguments)->get_span());
        // TODO remove this when implementing modules
        if ($namespace !== null) {
            $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
        }
        return new Include_Rule($name, $arguments, $span, $namespace, $content);
    }
    /**
     * Consumes a `@media` rule.
     *
     * $start should point before the `@`.
     */
    protected function media_rule(int $start): Media_Rule
    {
        $query = $this->media_query_list();
        return $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\Media_Rule => new Media_Rule($query, $children, $span));
    }
    /**
     * Consumes a mixin declaration.
     *
     * $start should point before the `@`.
     */
    private function mixin_rule(int $start): Mixin_Rule
    {
        $preceding_comment = $this->last_silent_comment;
        $this->last_silent_comment = null;
        $before_name = $this->scanner->get_position();
        $name = $this->identifier();
        if (str_starts_with($name, '--')) {
            Logger_Util::warn_for_deprecation($this->logger, Deprecation::cssFunctionMixin, "Sass @mixin names beginning with -- are deprecated for forward-compatibility with plain CSS mixins.\n\nFor details, see https://sass-lang.com/d/css-function-mixin", $this->scanner->span_from($before_name));
        }
        $this->whitespace();
        $arguments = $this->scanner->peek_char() === '(' ? $this->argument_declaration() : Argument_Declaration::create_empty($this->scanner->get_empty_span());
        if ($this->in_mixin || $this->in_content_block) {
            $this->error('Mixins may not contain mixin declarations.', $this->scanner->span_from($start));
        }
        if ($this->in_control_directive) {
            $this->error('Mixins may not be declared in control directives.', $this->scanner->span_from($start));
        }
        $this->whitespace();
        $this->in_mixin = true;
        return $this->with_children($this->statement(...), $start, function (array $children, File_Span $span) use ($name, $arguments, $preceding_comment): \Scss_Php\Scss_Php\Ast\Sass\Statement\Mixin_Rule {
            $this->in_mixin = false;
            return new Mixin_Rule($name, $arguments, $span, $children, $preceding_comment);
        });
    }
    /**
     * Consumes a `@moz-document` rule.
     *
     * Gecko's `@-moz-document` diverges from [the specification][] allows the
     * `url-prefix` and `domain` functions to omit quotation marks, contrary to
     * the standard.
     *
     * [the specification]: https://www.w3.org/TR/css3-conditional/
     */
    protected function moz_document_rule(int $start, Interpolation $name): At_Rule
    {
        $value_start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        $needs_deprecation_warning = false;
        while (true) {
            if ($this->scanner->peek_char() === '#') {
                $buffer->add($this->single_interpolation());
                $needs_deprecation_warning = true;
            } else {
                $identifier_start = $this->scanner->get_position();
                $identifier = $this->identifier();
                switch ($identifier) {
                    case 'url':
                    case 'url-prefix':
                    case 'domain':
                        $contents = $this->try_url_contents($identifier_start, $identifier);
                        if ($contents !== null) {
                            $buffer->add_interpolation($contents);
                        } else {
                            $this->scanner->expect_char('(');
                            $this->whitespace();
                            $argument = $this->interpolated_string();
                            $this->scanner->expect_char(')');
                            $buffer->write($identifier);
                            $buffer->write('(');
                            $buffer->add_interpolation($argument->as_interpolation());
                            $buffer->write(')');
                        }
                        // A url-prefix with no argument, or with an empty string as an
                        // argument, is not (yet) deprecated.
                        $trailing = $buffer->get_trailing_string();
                        if (!str_ends_with($trailing, 'url-prefix()') && !str_ends_with($trailing, "url-prefix('')") && !str_ends_with($trailing, 'url-prefix("")')) {
                            $needs_deprecation_warning = true;
                        }
                        break;
                    case 'regexp':
                        $buffer->write('regexp(');
                        $this->scanner->expect_char('(');
                        $buffer->add_interpolation($this->interpolated_string()->as_interpolation());
                        $this->scanner->expect_char(')');
                        $buffer->write(')');
                        $needs_deprecation_warning = true;
                        break;
                    default:
                        $this->error('Invalid function name.', $this->scanner->span_from($identifier_start));
                }
            }
            $this->whitespace();
            if (!$this->scanner->scan_char(',')) {
                break;
            }
            $buffer->write(',');
            $buffer->write($this->raw_text($this->whitespace(...)));
        }
        $value = $buffer->build_interpolation($this->scanner->span_from($value_start));
        return $this->with_children($this->statement(...), $start, function (array $children, File_Span $span) use ($name, $value, $needs_deprecation_warning): \Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule {
            if ($needs_deprecation_warning) {
                Logger_Util::warn_for_deprecation($this->logger, Deprecation::mozDocument, "@-moz-document is deprecated and support will be removed in Dart Sass 2.0.0.\n\nFor details, see https://sass-lang.com/d/moz-document.", $span);
            }
            return new At_Rule($name, $span, $value, $children);
        });
    }
    /**
     * Consumes a `@return` rule.
     *
     * $start should point before the `@`.
     */
    private function return_rule(int $start): Return_Rule
    {
        $value = $this->expression();
        $this->expect_statement_separator('@return rule');
        return new Return_Rule($value, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@supports` rule.
     *
     * $start should point before the `@`.
     */
    protected function supports_rule(int $start): Supports_Rule
    {
        $condition = $this->supports_condition();
        $this->whitespace();
        return $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\Supports_Rule => new Supports_Rule($condition, $children, $span));
    }
    /**
     * Consumes a `@warn` rule.
     *
     * $start should point before the `@`.
     */
    private function warn_rule(int $start): Warn_Rule
    {
        $value = $this->expression();
        $this->expect_statement_separator('@warn rule');
        return new Warn_Rule($value, $this->scanner->span_from($start));
    }
    /**
     * Consumes a `@while` rule.
     *
     * $start should point before the `@`. $child is called to consume any
     * children that are specifically allowed in the caller's context.
     *
     * @param callable(): Statement $child
     *
     * @param-immediately-invoked-callable $child
     */
    private function while_rule(int $start, callable $child): While_Rule
    {
        $was_in_control_directive = $this->in_control_directive;
        $this->in_control_directive = true;
        $condition = $this->expression();
        return $this->with_children($child, $start, function (array $children, File_Span $span) use ($condition, $was_in_control_directive): \Scss_Php\Scss_Php\Ast\Sass\Statement\While_Rule {
            $this->in_control_directive = $was_in_control_directive;
            return new While_Rule($condition, $children, $span);
        });
    }
    /**
     * Consumes an at-rule that's not explicitly supported by Sass.
     *
     * $start should point before the `@`. $name is the name of the at-rule.
     */
    protected function unknown_at_rule(int $start, Interpolation $name): At_Rule
    {
        $was_in_unknown_at_rule = $this->in_unknown_at_rule;
        $this->in_unknown_at_rule = true;
        $value = null;
        $next = $this->scanner->peek_char();
        if ($next !== '!' && !$this->at_end_of_statement()) {
            $value = $this->interpolated_declaration_value(allowOpenBrace: false);
        }
        if ($this->looking_at_children()) {
            $rule = $this->with_children($this->statement(...), $start, fn(array $children, File_Span $span): \Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule => new At_Rule($name, $span, $value, $children));
        } else {
            $this->expect_statement_separator();
            $rule = new At_Rule($name, $this->scanner->span_from($start), $value);
        }
        $this->in_unknown_at_rule = $was_in_unknown_at_rule;
        return $rule;
    }
    /**
     * Throws an exception indicating that the at-rule starting at $start is
     * not allowed in the current context.
     */
    private function disallowed_at_rule(int $start): never
    {
        $this->interpolated_declaration_value(allowEmpty: true, allowOpenBrace: false);
        $this->error('This at-rule is not allowed here.', $this->scanner->span_from($start));
    }
    /**
     * Consumes an argument declaration.
     */
    private function argument_declaration(): Argument_Declaration
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('(');
        $this->whitespace();
        $arguments = [];
        $named = [];
        $rest_argument = null;
        while ($this->scanner->peek_char() === '$') {
            $variable_start = $this->scanner->get_position();
            $name = $this->variable_name();
            $this->whitespace();
            $default_value = null;
            if ($this->scanner->scan_char(':')) {
                $this->whitespace();
                $default_value = $this->expression_until_comma();
            } elseif ($this->scanner->scan_char('.')) {
                $this->scanner->expect_char('.');
                $this->scanner->expect_char('.');
                $this->whitespace();
                $rest_argument = $name;
                break;
            }
            $argument = new Argument($name, $this->scanner->span_from($variable_start), $default_value);
            $arguments[] = $argument;
            if (isset($named[$name])) {
                $this->error('Duplicate argument.', $argument->get_span());
            }
            $named[$name] = true;
            if (!$this->scanner->scan_char(',')) {
                break;
            }
            $this->whitespace();
        }
        $this->scanner->expect_char(')');
        return new Argument_Declaration($arguments, $this->scanner->span_from($start), $rest_argument);
    }
    /**
     * Consumes an argument invocation.
     *
     * If $mixin is `true`, this is parsed as a mixin invocation. Mixin
     * invocations don't allow the Microsoft-style `=` operator at the top level,
     * but function invocations do.
     *
     * If $allowEmptySecondArg is `true`, this allows the second argument to be
     * omitted, in which case an unquoted empty string will be passed in its
     * place.
     */
    private function argument_invocation(bool $mixin = false, bool $allow_empty_second_arg = false): Argument_Invocation
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('(');
        $this->whitespace();
        $positional = [];
        $named = [];
        $rest = null;
        $keyword_rest = null;
        while ($this->looking_at_expression()) {
            $expression = $this->expression_until_comma(!$mixin);
            $this->whitespace();
            if ($expression instanceof Variable_Expression && $this->scanner->scan_char(':')) {
                $this->whitespace();
                if (isset($named[$expression->get_name()])) {
                    $this->error('Duplicate argument.', $expression->get_span());
                }
                $named[$expression->get_name()] = $this->expression_until_comma(!$mixin);
            } elseif ($this->scanner->scan_char('.')) {
                $this->scanner->expect_char('.');
                $this->scanner->expect_char('.');
                if ($rest === null) {
                    $rest = $expression;
                } else {
                    $keyword_rest = $expression;
                    $this->whitespace();
                    break;
                }
            } elseif ($named) {
                $this->error('Positional arguments must come before keyword arguments.', $expression->get_span());
            } else {
                $positional[] = $expression;
            }
            $this->whitespace();
            if (!$this->scanner->scan_char(',')) {
                break;
            }
            $this->whitespace();
            if ($allow_empty_second_arg && \count($positional) === 1 && \count($named) === 0 && $rest === null && $this->scanner->peek_char() === ')') {
                $positional[] = String_Expression::plain('', $this->scanner->get_empty_span());
                break;
            }
        }
        $this->scanner->expect_char(')');
        return new Argument_Invocation($positional, $named, $this->scanner->span_from($start), $rest, $keyword_rest);
    }
    /**
     * Consumes an expression.
     *
     * @param (callable(): bool)|null $until
     * @phpstan-impure
     */
    private function expression(?callable $until = null, bool $single_equals = false, bool $bracket_list = false): Expression
    {
        if ($until !== null && $until()) {
            $this->scanner->error('Expected expression.');
        }
        $before_bracket = null;
        if ($bracket_list) {
            $before_bracket = $this->scanner->get_position();
            $this->scanner->expect_char('[');
            $this->whitespace();
            if ($this->scanner->scan_char(']')) {
                return new List_Expression([], List_Separator::UNDECIDED, $this->scanner->span_from($before_bracket), true);
            }
        }
        $start = $this->scanner->get_position();
        $was_in_expression = $this->in_expression;
        $was_in_parentheses = $this->in_parentheses;
        $this->in_expression = true;
        /**
         * @var list<Expression>|null $commaExpressions
         */
        $comma_expressions = null;
        /**
         * @var list<Expression>|null $spaceExpressions
         */
        $space_expressions = null;
        /**
         * Operators whose right-hand $operands are not fully parsed yet, in order of
         * appearance in the document. Because a low-precedence operator will cause
         * parsing to finish for all preceding higher-precedence $operators, this is
         * naturally ordered from lowest to highest precedence.
         *
         * @var list<BinaryOperator>|null $operators
         */
        $operators = null;
        /**
         * The left-hand sides of $operators. `$operands[n]` is the left-hand side
         * of `$operators[n]`.
         *
         * @var list<Expression>|null $operands
         */
        $operands = null;
        /**
         * Whether the single expression parsed so far may be interpreted as
         * slash-separated numbers.
         */
        $allow_slash = true;
        /**
         * The leftmost expression that's been fully-parsed. This can be null in
         * special cases where the expression begins with a sub-expression but has
         * a later character that indicates that the outer expression isn't done,
         * as here:
         *
         *     foo, bar
         *         ^
         *
         * @var Expression|null $singleExpression
         */
        $single_expression = $this->single_expression();
        /**
         * Resets the scanner state to the state it was at the beginning of the
         * expression, except for {@see $inParentheses}.
         */
        $reset_state = function () use (&$comma_expressions, &$space_expressions, &$operators, &$operands, &$allow_slash, &$single_expression, $start): void {
            $comma_expressions = null;
            $space_expressions = null;
            $operators = null;
            $operands = null;
            $this->scanner->set_position($start);
            $allow_slash = true;
            $single_expression = $this->single_expression();
        };
        $resolve_one_operation = function () use (&$operands, &$operators, &$single_expression, &$allow_slash): void {
            assert($operands !== null);
            assert($operators !== null);
            $operator = array_pop($operators);
            assert($operator !== null, 'The list of operators must not be empty');
            $left = array_pop($operands);
            assert($left !== null, 'The list of operands must not be empty');
            $right = $single_expression;
            if ($right === null) {
                $this->scanner->error('Expected expression.', $this->scanner->get_position() - \strlen($operator->get_operator()), \strlen($operator->get_operator()));
            }
            if ($allow_slash && !$this->in_parentheses && $operator === Binary_Operator::DIVIDED_BY && self::is_slash_operand($left) && self::is_slash_operand($right)) {
                $single_expression = Binary_Operation_Expression::slash($left, $right);
            } else {
                $single_expression = new Binary_Operation_Expression($operator, $left, $right);
                $allow_slash = false;
                if ($operator === Binary_Operator::PLUS || $operator === Binary_Operator::MINUS) {
                    if ($this->scanner->substring($right->get_span()->get_start()->get_offset() - 1, $right->get_span()->get_start()->get_offset()) === $operator->get_operator() && Character::is_whitespace($this->scanner->get_string()[$left->get_span()->get_end()->get_offset()])) {
                        $operator_text = $operator->get_operator();
                        $message = <<<WARNING
                        This operation is parsed as:
                        
                            {$left} {$operator_text} {$right}
                        
                        but you may have intended it to mean:
                        
                            {$left} ({$operator_text}{$right})
                        
                        Add a space after {$operator_text} to clarify that it's meant to be a binary operation, or wrap
                        it in parentheses to make it a unary operation. This will be an error in future
                        versions of Sass.
                        
                        More info and automated migrator: https://sass-lang.com/d/strict-unary
                        WARNING;
                        Logger_Util::warn_for_deprecation($this->logger, Deprecation::strictUnary, $message, $single_expression->get_span());
                    }
                }
            }
        };
        $resolve_operations = function () use (&$operators, $resolve_one_operation): void {
            if ($operators === null) {
                return;
            }
            while ($operators) {
                $resolve_one_operation();
            }
        };
        $add_single_expression = function (Expression $expression) use (&$single_expression, &$allow_slash, &$space_expressions, $reset_state, $resolve_operations): void {
            if ($single_expression !== null) {
                // If we discover we're parsing a list whose first element is a division
                // operation, and we're in parentheses, reparse outside of a paren
                // context. This ensures that `(1/2 1)` doesn't perform division on its
                // first element.
                if ($this->in_parentheses) {
                    $this->in_parentheses = false;
                    if ($allow_slash) {
                        $reset_state();
                        return;
                    }
                }
                $space_expressions ??= [];
                $resolve_operations();
                $space_expressions[] = $single_expression;
                $allow_slash = true;
            }
            $single_expression = $expression;
        };
        $add_operator = function (Binary_Operator $operator) use (&$allow_slash, &$operators, &$operands, &$single_expression, $resolve_one_operation): void {
            if ($this->is_plain_css() && $operator !== Binary_Operator::SINGLE_EQUALS && $operator !== Binary_Operator::PLUS && $operator !== Binary_Operator::MINUS && $operator !== Binary_Operator::TIMES && $operator !== Binary_Operator::DIVIDED_BY) {
                $this->scanner->error("Operators aren't allowed in plain CSS.", $this->scanner->get_position() - \strlen($operator->get_operator()), \strlen($operator->get_operator()));
            }
            $allow_slash = $allow_slash && $operator === Binary_Operator::DIVIDED_BY;
            $operators ??= [];
            $operands ??= [];
            $precedence = $operator->get_precedence();
            while ($operators && $operators[\count($operators) - 1]->get_precedence() >= $precedence) {
                $resolve_one_operation();
            }
            $operators[] = $operator;
            if ($single_expression === null) {
                $this->scanner->error('Expected expression.', $this->scanner->get_position() - \strlen($operator->get_operator()), \strlen($operator->get_operator()));
            }
            $operands[] = $single_expression;
            $this->whitespace();
            $single_expression = $this->single_expression();
        };
        $resolve_space_expressions = function () use (&$space_expressions, &$single_expression, $resolve_operations): void {
            $resolve_operations();
            if ($space_expressions !== null) {
                if ($single_expression === null) {
                    $this->scanner->error('Expected expression.');
                }
                $space_expressions[] = $single_expression;
                $single_expression = new List_Expression($space_expressions, List_Separator::SPACE, $space_expressions[0]->get_span()->expand($space_expressions[\count($space_expressions) - 1]->get_span()));
                $space_expressions = null;
            }
        };
        while (true) {
            $this->whitespace();
            if ($until !== null && $until()) {
                break;
            }
            $first = $this->scanner->peek_char();
            switch ($first) {
                case '(':
                    // Parenthesized numbers can't be slash-separated.
                    $add_single_expression($this->parentheses());
                    break;
                case '[':
                    $add_single_expression($this->expression(null, false, true));
                    break;
                case '$':
                    $add_single_expression($this->variable());
                    break;
                case '&':
                    $add_single_expression($this->selector());
                    break;
                case "'":
                case '"':
                    $add_single_expression($this->interpolated_string());
                    break;
                case '#':
                    $add_single_expression($this->hash_expression());
                    break;
                case '=':
                    $this->scanner->read_char();
                    if ($single_equals && $this->scanner->peek_char() !== '=') {
                        $add_operator(Binary_Operator::SINGLE_EQUALS);
                    } else {
                        $this->scanner->expect_char('=');
                        $add_operator(Binary_Operator::EQUALS);
                    }
                    break;
                case '!':
                    $next = $this->scanner->peek_char(1);
                    if ($next === '=') {
                        $this->scanner->read_char();
                        $this->scanner->read_char();
                        $add_operator(Binary_Operator::NOT_EQUALS);
                    } elseif ($next === null || $next === 'i' || $next === 'I' || Character::is_whitespace($next)) {
                        $add_single_expression($this->important_expression());
                    } else {
                        break 2;
                    }
                    break;
                case '<':
                    $this->scanner->read_char();
                    $add_operator($this->scanner->scan_char('=') ? Binary_Operator::LESS_THAN_OR_EQUALS : Binary_Operator::LESS_THAN);
                    break;
                case '>':
                    $this->scanner->read_char();
                    $add_operator($this->scanner->scan_char('=') ? Binary_Operator::GREATER_THAN_OR_EQUALS : Binary_Operator::GREATER_THAN);
                    break;
                case '*':
                    $this->scanner->read_char();
                    $add_operator(Binary_Operator::TIMES);
                    break;
                case '+':
                    if ($single_expression === null) {
                        $add_single_expression($this->unary_operation());
                    } else {
                        $this->scanner->read_char();
                        $add_operator(Binary_Operator::PLUS);
                    }
                    break;
                case '-':
                    $next = $this->scanner->peek_char(1);
                    // Make sure `1-2` parses as `1 - 2`, not `1 (-2)`.
                    if ((Character::is_digit($next) || $next === '.') && ($single_expression === null || Character::is_whitespace($this->scanner->peek_char(-1)))) {
                        $add_single_expression($this->number());
                    } elseif ($this->looking_at_interpolated_identifier()) {
                        $add_single_expression($this->identifier_like());
                    } elseif ($single_expression === null) {
                        $add_single_expression($this->unary_operation());
                    } else {
                        $this->scanner->read_char();
                        $add_operator(Binary_Operator::MINUS);
                    }
                    break;
                case '/':
                    if ($single_expression === null) {
                        $add_single_expression($this->unary_operation());
                    } else {
                        $this->scanner->read_char();
                        $add_operator(Binary_Operator::DIVIDED_BY);
                    }
                    break;
                case '%':
                    $this->scanner->read_char();
                    $add_operator(Binary_Operator::MODULO);
                    break;
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    $add_single_expression($this->number());
                    break;
                case '.':
                    if ($this->scanner->peek_char(1) === '.') {
                        break 2;
                    }
                    $add_single_expression($this->number());
                    break;
                case 'a':
                    if (!$this->is_plain_css() && $this->scan_identifier('and')) {
                        $add_operator(Binary_Operator::AND);
                    } else {
                        $add_single_expression($this->identifier_like());
                    }
                    break;
                case 'o':
                    if (!$this->is_plain_css() && $this->scan_identifier('or')) {
                        $add_operator(Binary_Operator::OR);
                    } else {
                        $add_single_expression($this->identifier_like());
                    }
                    break;
                case 'u':
                case 'U':
                    if ($this->scanner->peek_char(1) === '+') {
                        $add_single_expression($this->unicode_range());
                    } else {
                        $add_single_expression($this->identifier_like());
                    }
                    break;
                case 'b':
                case 'c':
                case 'd':
                case 'e':
                case 'f':
                case 'g':
                case 'h':
                case 'i':
                case 'j':
                case 'k':
                case 'l':
                case 'm':
                case 'n':
                case 'p':
                case 'q':
                case 'r':
                case 's':
                case 't':
                case 'v':
                case 'w':
                case 'x':
                case 'y':
                case 'z':
                case 'A':
                case 'B':
                case 'C':
                case 'D':
                case 'E':
                case 'F':
                case 'G':
                case 'H':
                case 'I':
                case 'J':
                case 'K':
                case 'L':
                case 'M':
                case 'N':
                case 'O':
                case 'P':
                case 'Q':
                case 'R':
                case 'S':
                case 'T':
                case 'V':
                case 'W':
                case 'X':
                case 'Y':
                case 'Z':
                case '_':
                case '\\':
                    $add_single_expression($this->identifier_like());
                    break;
                case ',':
                    // If we discover we're parsing a list whose first element is a
                    // division operation, and we're in parentheses, reparse outside of a
                    // paren context. This ensures that `(1/2, 1)` doesn't perform division
                    // on its first element.
                    if ($this->in_parentheses) {
                        $this->in_parentheses = false;
                        if ($allow_slash) {
                            $reset_state();
                            break;
                        }
                    }
                    $comma_expressions ??= [];
                    if ($single_expression === null) {
                        $this->scanner->error('Expected expression.');
                    }
                    $resolve_space_expressions();
                    $comma_expressions[] = $single_expression;
                    $this->scanner->read_char();
                    $allow_slash = true;
                    $single_expression = null;
                    break;
                default:
                    if ($first !== null && \ord($first) >= 0x80) {
                        $add_single_expression($this->identifier_like());
                        break;
                    }
                    break 2;
            }
        }
        if ($bracket_list) {
            $this->scanner->expect_char(']');
        }
        if ($comma_expressions !== null) {
            $resolve_space_expressions();
            $this->in_parentheses = $was_in_parentheses;
            if ($single_expression !== null) {
                $comma_expressions[] = $single_expression;
            }
            $this->in_expression = $was_in_expression;
            return new List_Expression($comma_expressions, List_Separator::COMMA, $this->scanner->span_from($before_bracket ?? $start), $bracket_list);
        }
        if ($bracket_list && $space_expressions !== null) {
            $resolve_operations();
            $this->in_expression = $was_in_expression;
            assert($single_expression !== null);
            $space_expressions[] = $single_expression;
            return new List_Expression($space_expressions, List_Separator::SPACE, $this->scanner->span_from($before_bracket), true);
        }
        $resolve_space_expressions();
        assert($single_expression !== null);
        if ($bracket_list) {
            assert($before_bracket !== null);
            $single_expression = new List_Expression([$single_expression], List_Separator::UNDECIDED, $this->scanner->span_from($before_bracket), true);
        }
        $this->in_expression = $was_in_expression;
        return $single_expression;
    }
    /**
     * Consumes an expression until it reaches a top-level comma.
     *
     * If $singleEquals is true, this will allow the Microsoft-style `=`
     * operator at the top level.
     *
     * @phpstan-impure
     */
    protected function expression_until_comma(bool $single_equals = false): Expression
    {
        return $this->expression(fn(): bool => $this->scanner->peek_char() === ',', $single_equals);
    }
    /**
     * Whether $expression is allowed as an operand of a `/` expression that
     * produces a potentially slash-separated number.
     */
    private static function is_slash_operand(Expression $expression): bool
    {
        return $expression instanceof Number_Expression || $expression instanceof Function_Expression || $expression instanceof Binary_Operation_Expression && $expression->allows_slash();
    }
    /**
     * Consumes an expression that doesn't contain any top-level whitespace.
     */
    private function single_expression(): Expression
    {
        $first = $this->scanner->peek_char();
        switch ($first) {
            case '(':
                return $this->parentheses();
            case '/':
                return $this->unary_operation();
            case '.':
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                return $this->number();
            case '[':
                return $this->expression(null, false, true);
            case '$':
                return $this->variable();
            case '&':
                return $this->selector();
            case "'":
            case '"':
                return $this->interpolated_string();
            case '#':
                return $this->hash_expression();
            case '+':
                return $this->plus_expression();
            case '-':
                return $this->minus_expression();
            case '!':
                return $this->important_expression();
            case 'u':
            case 'U':
                if ($this->scanner->peek_char(1) === '+') {
                    return $this->unicode_range();
                }
                return $this->identifier_like();
            case 'a':
            case 'b':
            case 'c':
            case 'd':
            case 'e':
            case 'f':
            case 'g':
            case 'h':
            case 'i':
            case 'j':
            case 'k':
            case 'l':
            case 'm':
            case 'n':
            case 'o':
            case 'p':
            case 'q':
            case 'r':
            case 's':
            case 't':
            case 'v':
            case 'w':
            case 'x':
            case 'y':
            case 'z':
            case 'A':
            case 'B':
            case 'C':
            case 'D':
            case 'E':
            case 'F':
            case 'G':
            case 'H':
            case 'I':
            case 'J':
            case 'K':
            case 'L':
            case 'M':
            case 'N':
            case 'O':
            case 'P':
            case 'Q':
            case 'R':
            case 'S':
            case 'T':
            case 'V':
            case 'W':
            case 'X':
            case 'Y':
            case 'Z':
            case '_':
            case '\\':
                return $this->identifier_like();
            default:
                if ($first !== null && \ord($first) >= 0x80) {
                    return $this->identifier_like();
                }
                $this->scanner->error('Expected expression.');
        }
    }
    /**
     * Consumes a parenthesized expression.
     */
    protected function parentheses(): Expression
    {
        if ($this->is_plain_css()) {
            $this->scanner->error("Parentheses aren't allowed in plain CSS.");
        }
        $was_in_parentheses = $this->in_parentheses;
        $this->in_parentheses = true;
        try {
            $start = $this->scanner->get_position();
            $this->scanner->expect_char('(');
            $this->whitespace();
            if (!$this->looking_at_expression()) {
                $this->scanner->expect_char(')');
                return new List_Expression([], List_Separator::UNDECIDED, $this->scanner->span_from($start));
            }
            $first = $this->expression_until_comma();
            if ($this->scanner->scan_char(':')) {
                $this->whitespace();
                return $this->map($first, $start);
            }
            if (!$this->scanner->scan_char(',')) {
                $this->scanner->expect_char(')');
                return new Parenthesized_Expression($first, $this->scanner->span_from($start));
            }
            $this->whitespace();
            $expressions = [$first];
            while (true) {
                if (!$this->looking_at_expression()) {
                    break;
                }
                $expressions[] = $this->expression_until_comma();
                if (!$this->scanner->scan_char(',')) {
                    break;
                }
                $this->whitespace();
            }
            $this->scanner->expect_char(')');
            return new List_Expression($expressions, List_Separator::COMMA, $this->scanner->span_from($start));
        } finally {
            $this->in_parentheses = $was_in_parentheses;
        }
    }
    /**
     * Consumes a map expression.
     *
     * This expects to be called after the first colon in the map, with $first
     * as the expression before the colon and $start the point before the
     * opening parenthesis.
     */
    private function map(Expression $first, int $start): Map_Expression
    {
        $pairs = [[$first, $this->expression_until_comma()]];
        while ($this->scanner->scan_char(',')) {
            $this->whitespace();
            if (!$this->looking_at_expression()) {
                break;
            }
            $key = $this->expression_until_comma();
            $this->scanner->expect_char(':');
            $this->whitespace();
            $value = $this->expression_until_comma();
            $pairs[] = [$key, $value];
        }
        $this->scanner->expect_char(')');
        return new Map_Expression($pairs, $this->scanner->span_from($start));
    }
    /**
     * Consumes an expression that starts with a `#`.
     */
    private function hash_expression(): Expression
    {
        assert($this->scanner->peek_char() === '#');
        if ($this->scanner->peek_char(1) === '{') {
            return $this->identifier_like();
        }
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('#');
        $first = $this->scanner->peek_char();
        if ($first !== null && Character::is_digit($first)) {
            return new Color_Expression($this->hex_color_contents($start), $this->scanner->span_from($start));
        }
        $after_hash = $this->scanner->get_position();
        $identifier = $this->interpolated_identifier();
        if ($this->is_hex_color($identifier)) {
            $this->scanner->set_position($after_hash);
            return new Color_Expression($this->hex_color_contents($start), $this->scanner->span_from($start));
        }
        $buffer = new Interpolation_Buffer();
        $buffer->write('#');
        $buffer->add_interpolation($identifier);
        return new String_Expression($buffer->build_interpolation($this->scanner->span_from($start)));
    }
    /**
     * Consumes the contents of a hex color, after the `#`.
     */
    private function hex_color_contents(int $start): Sass_Color
    {
        $digit1 = $this->hex_digit();
        $digit2 = $this->hex_digit();
        $digit3 = $this->hex_digit();
        $alpha = null;
        if (!Character::is_hex($this->scanner->peek_char())) {
            // #abc
            $red = ($digit1 << 4) + $digit1;
            $green = ($digit2 << 4) + $digit2;
            $blue = ($digit3 << 4) + $digit3;
        } else {
            $digit4 = $this->hex_digit();
            if (!Character::is_hex($this->scanner->peek_char())) {
                #abcd
                $red = ($digit1 << 4) + $digit1;
                $green = ($digit2 << 4) + $digit2;
                $blue = ($digit3 << 4) + $digit3;
                $alpha = (($digit4 << 4) + $digit4) / 0xff;
            } else {
                $red = ($digit1 << 4) + $digit2;
                $green = ($digit3 << 4) + $digit4;
                $blue = ($this->hex_digit() << 4) + $this->hex_digit();
                if (Character::is_hex($this->scanner->peek_char())) {
                    $alpha = (($this->hex_digit() << 4) + $this->hex_digit()) / 0xff;
                }
            }
        }
        // Don't emit four- or eight-digit hex colors as hex, since that's not
        // yet well-supported in browsers.
        return Sass_Color::rgb_internal($red, $green, $blue, $alpha ?? 1.0, $alpha === null ? new Span_Color_Format($this->scanner->span_from($start)) : null);
    }
    private function is_hex_color(Interpolation $interpolation): bool
    {
        $plain = $interpolation->get_as_plain();
        if ($plain === null) {
            return false;
        }
        $length = \strlen($plain);
        if ($length !== 3 && $length !== 4 && $length !== 6 && $length !== 8) {
            return false;
        }
        for ($i = 0; $i < $length; $i++) {
            if (!Character::is_hex($plain[$i])) {
                return false;
            }
        }
        return true;
    }
    /**
     * Consumes a single hexadecimal digit.
     *
     * @phpstan-impure
     */
    private function hex_digit(): int
    {
        $char = $this->scanner->peek_char();
        if ($char === null || !Character::is_hex($char)) {
            $this->scanner->error('Expected hex digit.');
        }
        return (int) hexdec($this->scanner->read_char());
    }
    /**
     * Consumes an expression that starts with a `+`.
     */
    private function plus_expression(): Expression
    {
        assert($this->scanner->peek_char() === '+');
        $next = $this->scanner->peek_char(1);
        if (Character::is_digit($next) || $next === '.') {
            return $this->number();
        }
        return $this->unary_operation();
    }
    /**
     * Consumes an expression that starts with a `-`.
     */
    private function minus_expression(): Expression
    {
        assert($this->scanner->peek_char() === '-');
        $next = $this->scanner->peek_char(1);
        if (Character::is_digit($next) || $next === '.') {
            return $this->number();
        }
        if ($this->looking_at_interpolated_identifier()) {
            return $this->identifier_like();
        }
        return $this->unary_operation();
    }
    /**
     * Consumes an `!important` expression.
     */
    private function important_expression(): Expression
    {
        assert($this->scanner->peek_char() === '!');
        $start = $this->scanner->get_position();
        $this->scanner->read_char();
        $this->whitespace();
        $this->expect_identifier('important');
        return String_Expression::plain('!important', $this->scanner->span_from($start));
    }
    /**
     * Consumes a unary operation expression.
     */
    private function unary_operation(): Unary_Operation_Expression
    {
        $start = $this->scanner->get_position();
        $operator = $this->unary_operator_for($this->scanner->read_char());
        if ($operator === null) {
            $this->scanner->error('Expected unary operator.', $this->scanner->get_position() - 1);
        }
        if ($this->is_plain_css() && $operator !== Unary_Operator::DIVIDE) {
            $this->scanner->error("Operators aren't allowed in plain CSS.", $this->scanner->get_position() - 1, 1);
        }
        $this->whitespace();
        $operand = $this->single_expression();
        return new Unary_Operation_Expression($operator, $operand, $this->scanner->span_from($start));
    }
    /**
     * Returns the unary operator corresponding to $character, or `null` if
     * the character is not a unary operator.
     */
    private function unary_operator_for(string $character): ?Unary_Operator
    {
        return match ($character) {
            '+' => Unary_Operator::PLUS,
            '-' => Unary_Operator::MINUS,
            '/' => Unary_Operator::DIVIDE,
            default => null,
        };
    }
    /**
     * Consumes a number expression.
     */
    private function number(): Number_Expression
    {
        $start = $this->scanner->get_position();
        $first = $this->scanner->peek_char();
        if ($first === '+' || $first === '-') {
            $this->scanner->read_char();
        }
        if ($this->scanner->peek_char() !== '.') {
            $this->consume_natural_number();
        }
        // Don't complain about a dot after a number unless the number starts with a
        // dot. We don't allow a plain ".", but we need to allow "1." so that
        // "1..." will work as a rest argument.
        $this->try_decimal($this->scanner->get_position() !== $start && $first !== '+' && $first !== '-');
        $this->try_exponent();
        // Use PHP's built-in double parsing so that we don't accumulate
        // floating-point errors for numbers with lots of digits.
        $number = floatval($this->scanner->substring($start));
        $unit = null;
        if ($this->scanner->scan_char('%')) {
            $unit = '%';
        } elseif ($this->looking_at_identifier() && ($this->scanner->peek_char() !== '-' || $this->scanner->peek_char(1) !== '-')) {
            $unit = $this->identifier(false, true);
        }
        return new Number_Expression($number, $this->scanner->span_from($start), $unit);
    }
    /**
     * Consumes a natural number (that is, a non-negative integer).
     *
     * Doesn't support scientific notation.
     */
    private function consume_natural_number(): void
    {
        if (!Character::is_digit($this->scanner->read_char())) {
            $this->scanner->error('Expected digit.', $this->scanner->get_position() - 1);
        }
        while (Character::is_digit($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
    }
    /**
     * Consumes the decimal component of a number if it exists.
     *
     * If $allowTrailingDot is `false`, this will throw an error if there's a
     * dot without any numbers following it. Otherwise, it will ignore the dot
     * without consuming it.
     */
    private function try_decimal(bool $allow_trailing_dot = false): void
    {
        if ($this->scanner->peek_char() !== '.') {
            return;
        }
        if (!Character::is_digit($this->scanner->peek_char(1))) {
            if ($allow_trailing_dot) {
                return;
            }
            $this->scanner->error('Expected digit.', $this->scanner->get_position() + 1);
        }
        $this->scanner->read_char();
        while (Character::is_digit($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
    }
    /**
     * Consumes the exponent component of a number if it exists.
     */
    private function try_exponent(): void
    {
        $first = $this->scanner->peek_char();
        if ($first !== 'e' && $first !== 'E') {
            return;
        }
        $next = $this->scanner->peek_char(1);
        if (!Character::is_digit($next) && $next !== '-' && $next !== '+') {
            return;
        }
        $this->scanner->read_char();
        if ($next === '+' || $next === '-') {
            $this->scanner->read_char();
        }
        if (!Character::is_digit($this->scanner->peek_char())) {
            $this->scanner->error('Expected digit.');
        }
        while (Character::is_digit($this->scanner->peek_char())) {
            $this->scanner->read_char();
        }
    }
    /**
     * Consumes a unicode range expression.
     */
    private function unicode_range(): String_Expression
    {
        $start = $this->scanner->get_position();
        $this->expect_ident_char('u');
        $this->scanner->expect_char('+');
        $first_range_length = 0;
        while ($this->scan_char_if(Character::is_hex(...))) {
            $first_range_length++;
        }
        $has_question_mark = false;
        while ($this->scanner->scan_char('?')) {
            $has_question_mark = true;
            $first_range_length++;
        }
        if ($first_range_length === 0) {
            $this->scanner->error('Expected hex digit or "?".');
        } elseif ($first_range_length > 6) {
            $this->error('Expected at most 6 digits.', $this->scanner->span_from($start));
        } elseif ($has_question_mark) {
            return String_Expression::plain($this->scanner->substring($start), $this->scanner->span_from($start));
        }
        if ($this->scanner->scan_char('-')) {
            $second_range_start = $this->scanner->get_position();
            $second_range_length = 0;
            while ($this->scan_char_if(Character::is_hex(...))) {
                $second_range_length++;
            }
            if ($second_range_length === 0) {
                $this->scanner->error('Expected hex digit.');
            } elseif ($second_range_length > 6) {
                $this->error('Expected at most 6 digits.', $this->scanner->span_from($second_range_start));
            }
        }
        if ($this->looking_at_interpolated_identifier_body()) {
            $this->scanner->error('Expected end of identifier.');
        }
        return String_Expression::plain($this->scanner->substring($start), $this->scanner->span_from($start));
    }
    /**
     * Consumes a variable expression.
     */
    private function variable(): Variable_Expression
    {
        $start = $this->scanner->get_position();
        $name = $this->variable_name();
        if ($this->is_plain_css()) {
            $this->error('Sass variables aren\'t allowed in plain CSS.', $this->scanner->span_from($start));
        }
        return new Variable_Expression($name, $this->scanner->span_from($start));
    }
    /**
     * Consumes a selector expression.
     */
    private function selector(): Selector_Expression
    {
        if ($this->is_plain_css()) {
            $this->scanner->error("The parent selector isn't allowed in plain CSS.", null, 1);
        }
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('&');
        if ($this->scanner->scan_char('&')) {
            $this->warn('In Sass, "&&" means two copies of the parent selector. You probably want to use "and" instead.', $this->scanner->span_from($start));
            $this->scanner->set_position($this->scanner->get_position() - 1);
        }
        return new Selector_Expression($this->scanner->span_from($start));
    }
    /**
     * Consumes a quoted string expression.
     */
    protected function interpolated_string(): String_Expression
    {
        $start = $this->scanner->get_position();
        $quote = $this->scanner->read_char();
        if ($quote !== "'" && $quote !== '"') {
            $this->scanner->error('Expected string.', $start);
        }
        $buffer = new Interpolation_Buffer();
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === $quote) {
                $this->scanner->read_char();
                break;
            }
            if ($next === null || Character::is_newline($next)) {
                $this->scanner->error("Expected {$quote}.");
            }
            if ($next === '\\') {
                $second = $this->scanner->peek_char(1);
                if (Character::is_newline($second)) {
                    $this->scanner->read_char();
                    $this->scanner->read_char();
                    if ($second === "\r") {
                        $this->scanner->scan_char("\n");
                    }
                } else {
                    $buffer->write($this->escape_character());
                }
            } elseif ($next === '#') {
                if ($this->scanner->peek_char(1) === '{') {
                    $buffer->add($this->single_interpolation());
                } else {
                    $buffer->write($this->scanner->read_char());
                }
            } else {
                $buffer->write($this->scanner->read_utf8char());
            }
        }
        return new String_Expression($buffer->build_interpolation($this->scanner->span_from($start)), true);
    }
    /**
     * Consumes an expression that starts like an identifier.
     */
    protected function identifier_like(): Expression
    {
        $start = $this->scanner->get_position();
        $identifier = $this->interpolated_identifier();
        $plain = $identifier->get_as_plain();
        if ($plain !== null) {
            if ($plain === 'if' && $this->scanner->peek_char() === '(') {
                $invocation = $this->argument_invocation();
                return new If_Expression($invocation, $identifier->get_span()->expand($invocation->get_span()));
            }
            if ($plain === 'not') {
                $this->whitespace();
                $expression = $this->single_expression();
                return new Unary_Operation_Expression(Unary_Operator::NOT, $expression, $identifier->get_span()->expand($expression->get_span()));
            }
            $lower = strtolower($plain);
            if ($this->scanner->peek_char() !== '(') {
                switch ($plain) {
                    case 'false':
                        return new Boolean_Expression(false, $identifier->get_span());
                    case 'null':
                        return new Null_Expression($identifier->get_span());
                    case 'true':
                        return new Boolean_Expression(true, $identifier->get_span());
                }
                $color = Colors::color_name_to_color($lower);
                if ($color !== null) {
                    return new Color_Expression(Sass_Color::rgb_internal($color->get_red(), $color->get_green(), $color->get_blue(), $color->get_alpha(), new Span_Color_Format($identifier->get_span())), $identifier->get_span());
                }
            }
            $special_function = $this->try_special_function($lower, $start);
            if ($special_function !== null) {
                return $special_function;
            }
        }
        switch ($this->scanner->peek_char()) {
            case '.':
                if ($this->scanner->peek_char(1) === '.') {
                    return new String_Expression($identifier);
                }
                $this->scanner->read_char();
                if ($plain !== null) {
                    return $this->namespaced_expression($plain, $start);
                }
                $this->error("Interpolation isn't allowed in namespaces.", $identifier->get_span());
            // no break
            case '(':
                if ($plain === null) {
                    return new Interpolated_Function_Expression($identifier, $this->argument_invocation(), $this->scanner->span_from($start));
                }
                return new Function_Expression($plain, $this->argument_invocation(false, $lower === 'var'), $this->scanner->span_from($start));
            default:
                return new String_Expression($identifier);
        }
    }
    /**
     * Consumes an expression after a namespace.
     *
     * This assumes the scanner is positioned immediately after the `.`. The
     * $start should refer to the state at the beginning of the namespace.
     */
    protected function namespaced_expression(string $namespace, int $start): Expression
    {
        if ($this->scanner->peek_char() === '$') {
            $name = $this->variable_name();
            $this->assert_public($name, fn(): \Source_Span\File_Span => $this->scanner->span_from($start));
            // TODO remove this when implementing modules
            $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
            // return new VariableExpression($name, $this->scanner->spanFrom($start), $plain);
        }
        // TODO remove this when implementing modules
        $this->public_identifier();
        $this->error('Sass modules are not implemented yet.', $this->scanner->span_from($start));
        // return new FunctionExpression($this->publicIdentifier(), $this->argumentInvocation(), $this->scanner->spanFrom($start), $plain);
    }
    /**
     * If $name is the name of a function with special syntax, consumes it.
     *
     * Otherwise, returns `null`. $start is the location before the beginning of $name.
     */
    protected function try_special_function(string $name, int $start): ?Expression
    {
        $normalized = Util::unvendor($name);
        switch ($normalized) {
            case 'calc':
                if ($normalized === $name) {
                    return null;
                }
            // fall through
            // no break
            case 'element':
            case 'expression':
                if (!$this->scanner->scan_char('(')) {
                    return null;
                }
                $buffer = new Interpolation_Buffer();
                $buffer->write($name);
                $buffer->write('(');
                break;
            case 'progid':
                if (!$this->scanner->scan_char(':')) {
                    return null;
                }
                $buffer = new Interpolation_Buffer();
                $buffer->write($name);
                $buffer->write(':');
                $next = $this->scanner->peek_char();
                while ($next !== null && (Character::is_alphabetic($next) || $next === '.')) {
                    $buffer->write($this->scanner->read_char());
                    $next = $this->scanner->peek_char();
                }
                $this->scanner->expect_char('(');
                $buffer->write('(');
                break;
            case 'url':
                $contents = $this->try_url_contents($start);
                if ($contents === null) {
                    return null;
                }
                return new String_Expression($contents);
            default:
                return null;
        }
        $buffer->add_interpolation($this->interpolated_declaration_value(true));
        $this->scanner->expect_char(')');
        $buffer->write(')');
        return new String_Expression($buffer->build_interpolation($this->scanner->span_from($start)));
    }
    private function try_url_contents(int $start, ?string $name = null): ?Interpolation
    {
        $beginning_of_contents = $this->scanner->get_position();
        if (!$this->scanner->scan_char('(')) {
            return null;
        }
        $this->whitespace_without_comments();
        $buffer = new Interpolation_Buffer();
        $buffer->write($name ?? 'url');
        $buffer->write('(');
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            if ($next === '\\') {
                $buffer->write($this->escape());
            } elseif ($next === '!' || $next === '%' || $next === '&' || \ord($next) >= \ord('*') && \ord($next) <= \ord('~') || \ord($next) >= 0x80) {
                $buffer->write($this->scanner->read_utf8char());
            } elseif ($next === '#') {
                if ($this->scanner->peek_char(1) === '{') {
                    $buffer->add($this->single_interpolation());
                } else {
                    $buffer->write($this->scanner->read_char());
                }
            } elseif (Character::is_whitespace($next)) {
                $this->whitespace_without_comments();
                if ($this->scanner->peek_char() !== ')') {
                    break;
                }
            } elseif ($next === ')') {
                $buffer->write($this->scanner->read_char());
                return $buffer->build_interpolation($this->scanner->span_from($start));
            } else {
                break;
            }
        }
        $this->scanner->set_position($beginning_of_contents);
        return null;
    }
    /**
     * Consumes a `url` token that's allowed to contain SassScript.
     */
    protected function dynamic_url(): Expression
    {
        $start = $this->scanner->get_position();
        $this->expect_identifier('url');
        $contents = $this->try_url_contents($start);
        if ($contents !== null) {
            return new String_Expression($contents);
        }
        return new Interpolated_Function_Expression(new Interpolation(['url'], $this->scanner->span_from($start)), $this->argument_invocation(), $this->scanner->span_from($start));
    }
    /**
     * Consumes tokens up to "{", "}", ";", or "!".
     *
     * This respects string and comment boundaries and supports interpolation.
     * Once this interpolation is evaluated, it's expected to be re-parsed.
     *
     * If $omitComments is true, comments will still be consumed, but they will
     * not be included in the returned interpolation.
     *
     * Differences from {@see interpolatedDeclarationValue} include:
     *
     * - This always stops at curly braces.
     * - This does not interpret backslashes, since the text is expected to be
     *   re-parsed.
     * - This does not compress adjacent whitespace characters.
     */
    protected function almost_any_value(bool $omit_comments = false): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        while (true) {
            $next = $this->scanner->peek_char();
            switch ($next) {
                case '\\':
                    // Write a literal backslash because this text will be re-parsed.
                    $buffer->write($this->scanner->read_char());
                    $buffer->write($this->scanner->read_utf8char());
                    break;
                case '"':
                case "'":
                    $buffer->add_interpolation($this->interpolated_string()->as_interpolation());
                    break;
                case '/':
                    switch ($this->scanner->peek_char(1)) {
                        case '*':
                            if (!$omit_comments) {
                                $buffer->write($this->raw_text($this->loud_comment(...)));
                            } else {
                                $this->loud_comment();
                            }
                            break;
                        case '/':
                            if (!$omit_comments) {
                                $buffer->write($this->raw_text($this->silent_comment(...)));
                            } else {
                                $this->silent_comment();
                            }
                            break;
                        default:
                            $buffer->write($this->scanner->read_char());
                    }
                    break;
                case '#':
                    if ($this->scanner->peek_char(1) === '{') {
                        // Add a full interpolated identifier to handle cases like
                        // "#{...}--1", since "--1" isn't a valid identifier on its own.
                        $buffer->add_interpolation($this->interpolated_identifier());
                    } else {
                        $buffer->write($this->scanner->read_char());
                    }
                    break;
                case "\r":
                case "\n":
                case "\f":
                    if ($this->is_indented()) {
                        break 2;
                    }
                    $buffer->write($this->scanner->read_char());
                    break;
                case '!':
                case ';':
                case '{':
                case '}':
                    break 2;
                case 'u':
                case 'U':
                    $before_url = $this->scanner->get_position();
                    $identifier = $this->identifier();
                    if ($identifier !== 'url' && $identifier !== 'url-prefix') {
                        $buffer->write($identifier);
                        continue 2;
                    }
                    $contents = $this->try_url_contents($before_url, $identifier);
                    if ($contents === null) {
                        $this->scanner->set_position($before_url);
                        $buffer->write($this->scanner->read_char());
                    } else {
                        $buffer->add_interpolation($contents);
                    }
                    break;
                default:
                    if ($next === null) {
                        break 2;
                    }
                    if ($this->looking_at_identifier()) {
                        $buffer->write($this->identifier());
                    } else {
                        $buffer->write($this->scanner->read_utf8char());
                    }
                    break;
            }
        }
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes tokens until it reaches a top-level `";"`, `")"`, `"]"`,
     * or `"}"` and returns their contents as a string.
     *
     * If $allowEmpty is `false` (the default), this requires at least one token.
     *
     * If $allowSemicolon is `true`, this doesn't stop at semicolons and instead
     * includes them in the interpolated output.
     *
     * If $allowColon is `false`, this stops at top-level colons.
     *
     * If $allowOpenBrace is `false`, this stops at opening curly braces.
     *
     * If $silentComments is `true`, this will parse silent comments as
     * comments. Otherwise, it will preserve two adjacent slashes and emit them
     * to CSS.
     *
     * Unlike {@see declarationValue}, this allows interpolation.
     */
    private function interpolated_declaration_value(bool $allow_empty = false, bool $allow_semicolon = false, bool $allow_colon = true, bool $allow_open_brace = true, bool $silent_comments = true): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        $brackets = [];
        $wrote_newline = false;
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            switch ($next) {
                case '\\':
                    $buffer->write($this->escape(true));
                    $wrote_newline = false;
                    break;
                case '"':
                case "'":
                    $buffer->add_interpolation($this->interpolated_string()->as_interpolation());
                    $wrote_newline = false;
                    break;
                case '/':
                    $peeked_char = $this->scanner->peek_char(1);
                    if ($peeked_char === '*') {
                        $buffer->write($this->raw_text($this->loud_comment(...)));
                    } elseif ($peeked_char === '/' && $silent_comments) {
                        $this->silent_comment();
                    } else {
                        $buffer->write($this->scanner->read_char());
                    }
                    $wrote_newline = false;
                    break;
                case '#':
                    if ($this->scanner->peek_char(1) === '{') {
                        // Add a full interpolated identifier to handle cases like
                        // "#{...}--1", since "--1" isn't a valid identifier on its own.
                        $buffer->add_interpolation($this->interpolated_identifier());
                    } else {
                        $buffer->write($this->scanner->read_char());
                    }
                    $wrote_newline = false;
                    break;
                case ' ':
                case "\t":
                    $second = $this->scanner->peek_char(1);
                    if ($wrote_newline || $second === null || !Character::is_whitespace($second)) {
                        $buffer->write($this->scanner->read_char());
                    } else {
                        $this->scanner->read_char();
                    }
                    break;
                case "\n":
                case "\r":
                case "\f":
                    if ($this->is_indented()) {
                        break 2;
                    }
                    $prev = $this->scanner->peek_char(-1);
                    if ($prev === null || !Character::is_newline($prev)) {
                        $buffer->write("\n");
                    }
                    $this->scanner->read_char();
                    $wrote_newline = true;
                    break;
                case '{':
                    if (!$allow_open_brace) {
                        break 2;
                    }
                // Fallthrough
                // no break
                case '(':
                case '[':
                    $bracket = $this->scanner->read_char();
                    $buffer->write($bracket);
                    $brackets[] = Character::opposite($bracket);
                    $wrote_newline = false;
                    break;
                case ')':
                case '}':
                case ']':
                    if (empty($brackets)) {
                        break 2;
                    }
                    $bracket = array_pop($brackets);
                    $this->scanner->expect_char($bracket);
                    $buffer->write($bracket);
                    $wrote_newline = false;
                    break;
                case ';':
                    if (!$allow_semicolon && empty($brackets)) {
                        break 2;
                    }
                    $buffer->write($this->scanner->read_char());
                    $wrote_newline = false;
                    break;
                case ':':
                    if (!$allow_colon && empty($brackets)) {
                        break 2;
                    }
                    $buffer->write($this->scanner->read_char());
                    $wrote_newline = false;
                    break;
                case 'u':
                case 'U':
                    $before_url = $this->scanner->get_position();
                    $identifier = $this->identifier();
                    if ($identifier !== 'url' && $identifier !== 'url-prefix') {
                        $buffer->write($identifier);
                        $wrote_newline = false;
                        continue 2;
                    }
                    $contents = $this->try_url_contents($before_url, $identifier);
                    if ($contents === null) {
                        $this->scanner->set_position($before_url);
                        $buffer->write($this->scanner->read_char());
                    } else {
                        $buffer->add_interpolation($contents);
                    }
                    $wrote_newline = false;
                    break;
                default:
                    if ($this->looking_at_identifier()) {
                        $buffer->write($this->identifier());
                    } else {
                        $buffer->write($this->scanner->read_utf8char());
                    }
                    $wrote_newline = false;
                    break;
            }
        }
        if (!empty($brackets)) {
            $this->scanner->expect_char(array_pop($brackets));
        }
        if (!$allow_empty && $buffer->is_empty()) {
            $this->scanner->error('Expected token.');
        }
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes an identifier that may contain interpolation.
     */
    protected function interpolated_identifier(): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        if ($this->scanner->scan_char('-')) {
            $buffer->write('-');
            if ($this->scanner->scan_char('-')) {
                $buffer->write('-');
                $this->interpolated_identifier_body($buffer);
                return $buffer->build_interpolation($this->scanner->span_from($start));
            }
        }
        $first = $this->scanner->peek_char();
        if ($first === null) {
            $this->scanner->error('Expected identifier.');
        }
        if (Character::is_name_start($first)) {
            $buffer->write($this->scanner->read_utf8char());
        } elseif ($first === '\\') {
            $buffer->write($this->escape(true));
        } elseif ($first === '#' && $this->scanner->peek_char(1) === '{') {
            $buffer->add($this->single_interpolation());
        } else {
            $this->scanner->error('Expected identifier.');
        }
        $this->interpolated_identifier_body($buffer);
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes a chunk of a possibly-interpolated CSS identifier after the name
     * start, and adds the contents to the $buffer buffer.
     */
    private function interpolated_identifier_body(Interpolation_Buffer $buffer): void
    {
        while (true) {
            $next = $this->scanner->peek_char();
            if ($next === null) {
                break;
            }
            if ($next === '_' || $next === '-' || Character::is_alphanumeric($next) || \ord($next) >= 0x80) {
                $buffer->write($this->scanner->read_utf8char());
            } elseif ($next === '\\') {
                $buffer->write($this->escape());
            } elseif ($next === '#' && $this->scanner->peek_char(1) === '{') {
                $buffer->add($this->single_interpolation());
            } else {
                break;
            }
        }
    }
    /**
     * Consumes interpolation.
     */
    protected function single_interpolation(): Expression
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect('#{');
        $this->whitespace();
        $contents = $this->expression();
        $this->scanner->expect_char('}');
        if ($this->is_plain_css()) {
            $this->error('Interpolation isn\'t allowed in plain CSS.', $this->scanner->span_from($start));
        }
        return $contents;
    }
    /**
     * Consumes a list of media queries.
     */
    private function media_query_list(): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        while (true) {
            $this->whitespace();
            $this->media_query($buffer);
            $this->whitespace();
            if (!$this->scanner->scan_char(',')) {
                break;
            }
            $buffer->write(', ');
        }
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    /**
     * Consumes a single media query.
     */
    private function media_query(Interpolation_Buffer $buffer): void
    {
        if ($this->scanner->peek_char() === '(') {
            $this->media_in_parens($buffer);
            $this->whitespace();
            if ($this->scan_identifier('and')) {
                $buffer->write(' and ');
                $this->expect_whitespace();
                $this->media_logic_sequence($buffer, 'and');
            } elseif ($this->scan_identifier('or')) {
                $buffer->write(' or ');
                $this->expect_whitespace();
                $this->media_logic_sequence($buffer, 'or');
            }
            return;
        }
        $identifier1 = $this->interpolated_identifier();
        if (String_Util::equals_ignore_case($identifier1->get_as_plain(), 'not')) {
            // For example, "@media not (...) {"
            $this->expect_whitespace();
            if (!$this->looking_at_interpolated_identifier()) {
                $buffer->write('not ');
                $this->media_or_interp($buffer);
                return;
            }
        }
        $this->whitespace();
        $buffer->add_interpolation($identifier1);
        if (!$this->looking_at_interpolated_identifier()) {
            // For example, "@media screen {".
            return;
        }
        $buffer->write(' ');
        $identifier2 = $this->interpolated_identifier();
        if (String_Util::equals_ignore_case($identifier2->get_as_plain(), 'and')) {
            $this->expect_whitespace();
            // For example, "@media screen and ..."
            $buffer->write(' and ');
        } else {
            $this->whitespace();
            $buffer->add_interpolation($identifier2);
            if ($this->scan_identifier('and')) {
                // For example, "@media only screen and ..."
                $this->expect_whitespace();
                $buffer->write(' and ');
            } else {
                // For example, "@media only screen {"
                return;
            }
        }
        // We've consumed either `IDENTIFIER "and"` or
        // `IDENTIFIER IDENTIFIER "and"`.
        if ($this->scan_identifier('not')) {
            // For example, "@media screen and not (...) {"
            $this->expect_whitespace();
            $buffer->write('not ');
            $this->media_or_interp($buffer);
            return;
        }
        $this->media_logic_sequence($buffer, 'and');
    }
    /**
     * Consumes one or more `MediaOrInterp` expressions separated by $operator
     * and writes them to $buffer.
     */
    private function media_logic_sequence(Interpolation_Buffer $buffer, string $operator): void
    {
        while (true) {
            $this->media_or_interp($buffer);
            $this->whitespace();
            if (!$this->scan_identifier($operator)) {
                return;
            }
            $this->expect_whitespace();
            $buffer->write(' ');
            $buffer->write($operator);
            $buffer->write(' ');
        }
    }
    /**
     * Consumes a `MediaOrInterp` expression and writes it to $buffer.
     */
    private function media_or_interp(Interpolation_Buffer $buffer): void
    {
        if ($this->scanner->peek_char() === '#') {
            $interpolation = $this->single_interpolation();
            $buffer->add_interpolation(new Interpolation([$interpolation], $interpolation->get_span()));
        } else {
            $this->media_in_parens($buffer);
        }
    }
    /**
     * Consumes a `MediaInParens` expression and writes it to $buffer.
     */
    private function media_in_parens(Interpolation_Buffer $buffer): void
    {
        $this->scanner->expect_char('(', 'media condition in parentheses');
        $buffer->write('(');
        $this->whitespace();
        if ($this->scanner->peek_char() === '(') {
            $this->media_in_parens($buffer);
            $this->whitespace();
            if ($this->scan_identifier('and')) {
                $buffer->write(' and ');
                $this->expect_whitespace();
                $this->media_logic_sequence($buffer, 'and');
            } elseif ($this->scan_identifier('or')) {
                $buffer->write(' or ');
                $this->expect_whitespace();
                $this->media_logic_sequence($buffer, 'or');
            }
        } elseif ($this->scan_identifier('not')) {
            $buffer->write('not ');
            $this->expect_whitespace();
            $this->media_or_interp($buffer);
        } else {
            $buffer->add($this->expression_until_comparison());
            if ($this->scanner->scan_char(':')) {
                $this->whitespace();
                $buffer->write(': ');
                $buffer->add($this->expression());
            } else {
                $next = $this->scanner->peek_char();
                if ($next === '<' || $next === '>' || $next === '=') {
                    $buffer->write(' ');
                    $buffer->write($this->scanner->read_char());
                    if (($next === '<' || $next === '>') && $this->scanner->scan_char('=')) {
                        $buffer->write('=');
                    }
                    $buffer->write(' ');
                    $this->whitespace();
                    $buffer->add($this->expression_until_comparison());
                    if (($next === '<' || $next === '>') && $this->scanner->scan_char($next)) {
                        $buffer->write(' ');
                        $buffer->write($next);
                        if ($this->scanner->scan_char('=')) {
                            $buffer->write('=');
                        }
                        $buffer->write(' ');
                        $this->whitespace();
                        $buffer->add($this->expression_until_comparison());
                    }
                }
            }
        }
        $this->scanner->expect_char(')');
        $this->whitespace();
        $buffer->write(')');
    }
    /**
     * Consumes an expression until it reaches a top-level `<`, `>`, or a `=`
     * that's not `==`.
     */
    private function expression_until_comparison(): Expression
    {
        return $this->expression(function (): bool {
            $next = $this->scanner->peek_char();
            if ($next === '=') {
                return $this->scanner->peek_char(1) !== '=';
            }
            return $next === '<' || $next === '>';
        });
    }
    /**
     * Consumes a `@supports` condition.
     */
    private function supports_condition(): Supports_Condition
    {
        $start = $this->scanner->get_position();
        if ($this->scan_identifier('not')) {
            $this->whitespace();
            return new Supports_Negation($this->supports_condition_in_parens(), $this->scanner->span_from($start));
        }
        $condition = $this->supports_condition_in_parens();
        $this->whitespace();
        $operator = null;
        while ($this->looking_at_identifier()) {
            if ($operator !== null) {
                $this->expect_identifier($operator);
            } elseif ($this->scan_identifier('or')) {
                $operator = 'or';
            } else {
                $this->expect_identifier('and');
                $operator = 'and';
            }
            $this->whitespace();
            $right = $this->supports_condition_in_parens();
            $condition = new Supports_Operation($condition, $right, $operator, $this->scanner->span_from($start));
            $this->whitespace();
        }
        return $condition;
    }
    /**
     * Consumes a parenthesized supports condition, or an interpolation.
     */
    private function supports_condition_in_parens(): Supports_Condition
    {
        $start = $this->scanner->get_position();
        if ($this->looking_at_interpolated_identifier()) {
            $identifier = $this->interpolated_identifier();
            if ($identifier->get_as_plain() !== null && strtolower($identifier->get_as_plain()) === 'not') {
                $this->error('"not" is not a valid identifier here.', $identifier->get_span());
            }
            if ($this->scanner->scan_char('(')) {
                $arguments = $this->interpolated_declaration_value(true, true);
                $this->scanner->expect_char(')');
                return new Supports_Function($identifier, $arguments, $this->scanner->span_from($start));
            }
            if (\count($identifier->get_contents()) !== 1 || !$identifier->get_contents()[0] instanceof Expression) {
                $this->error('Expected @supports condition.', $identifier->get_span());
            } else {
                return new Supports_Interpolation($identifier->get_contents()[0], $identifier->get_span());
            }
        }
        $this->scanner->expect_char('(');
        $this->whitespace();
        if ($this->scan_identifier('not')) {
            $this->whitespace();
            $condition = $this->supports_condition_in_parens();
            $this->scanner->expect_char(')');
            return new Supports_Negation($condition, $this->scanner->span_from($start));
        }
        if ($this->scanner->peek_char() === '(') {
            $condition = $this->supports_condition();
            $this->scanner->expect_char(')');
            return $condition;
        }
        // Unfortunately, we may have to backtrack here. The grammar is:
        //
        //       Expression ":" Expression
        //     | InterpolatedIdentifier InterpolatedAnyValue?
        //
        // These aren't ambiguous because this `InterpolatedAnyValue` is forbidden
        // from containing a top-level colon, but we still have to parse the full
        // expression to figure out if there's a colon after it.
        //
        // We could avoid the overhead of a full expression parse by looking ahead
        // for a colon (outside of balanced brackets), but in practice we expect the
        // vast majority of real uses to be `Expression ":" Expression`, so it makes
        // sense to parse that case faster in exchange for less code complexity and
        // a slower backtracking case.
        $name_start = $this->scanner->get_position();
        $was_in_parentheses = $this->in_parentheses;
        try {
            $name = $this->expression();
            $this->scanner->expect_char(':');
        } catch (Format_Exception $e) {
            $this->scanner->set_position($name_start);
            $this->in_parentheses = $was_in_parentheses;
            $identifier = $this->interpolated_identifier();
            $operation = $this->try_supports_operation($identifier, $name_start);
            if ($operation !== null) {
                $this->scanner->expect_char(')');
                return $operation;
            }
            // If parsing an expression fails, try to parse an
            // `InterpolatedAnyValue` instead. But if that value runs into a
            // top-level colon, then this is probably intended to be a declaration
            // after all, so we rethrow the declaration-parsing error.
            $buffer = new Interpolation_Buffer();
            $buffer->add_interpolation($identifier);
            $buffer->add_interpolation($this->interpolated_declaration_value(true, true, false));
            $contents = $buffer->build_interpolation($this->scanner->span_from($name_start));
            if ($this->scanner->peek_char() === ':') {
                throw $e;
            }
            $this->scanner->expect_char(')');
            return new Supports_Anything($contents, $this->scanner->span_from($start));
        }
        $declaration = $this->supports_declaration_value($name, $start);
        $this->scanner->expect_char(')');
        return $declaration;
    }
    private function supports_declaration_value(Expression $name, int $start): Supports_Declaration
    {
        if ($name instanceof String_Expression && !$name->has_quotes() && str_starts_with($name->get_text()->get_initial_plain(), '--')) {
            $value = new String_Expression($this->interpolated_declaration_value());
        } else {
            $this->whitespace();
            $value = $this->expression();
        }
        return new Supports_Declaration($name, $value, $this->scanner->span_from($start));
    }
    /**
     * If $interpolation is followed by `"and"` or `"or"`, parse it as a supports operation.
     *
     * Otherwise, return `null` without moving the scanner position.
     */
    private function try_supports_operation(Interpolation $interpolation, int $start): ?Supports_Operation
    {
        if (\count($interpolation->get_contents()) !== 1) {
            return null;
        }
        $expression = $interpolation->get_contents()[0];
        if (!$expression instanceof Expression) {
            return null;
        }
        $before_whitespace = $this->scanner->get_position();
        $this->whitespace();
        $operation = null;
        $operator = null;
        while ($this->looking_at_identifier()) {
            if ($operator !== null) {
                $this->expect_identifier($operator);
            } elseif ($this->scan_identifier('and')) {
                $operator = 'and';
            } elseif ($this->scan_identifier('or')) {
                $operator = 'or';
            } else {
                $this->scanner->set_position($before_whitespace);
                return null;
            }
            $this->whitespace();
            $right = $this->supports_condition_in_parens();
            $operation = new Supports_Operation($operation ?? new Supports_Interpolation($expression, $interpolation->get_span()), $right, $operator, $this->scanner->span_from($start));
            $this->whitespace();
        }
        return $operation;
    }
    /**
     * Returns whether the scanner is immediately before an identifier that may
     * contain interpolation.
     *
     * This is based on [the CSS algorithm][], but it assumes all backslashes
     * start escapes and it considers interpolation to be valid in an identifier.
     *
     * [the CSS algorithm]: https://drafts.csswg.org/css-syntax-3/#would-start-an-identifier
     */
    private function looking_at_interpolated_identifier(): bool
    {
        $first = $this->scanner->peek_char();
        if ($first === null) {
            return false;
        }
        if ($first === '\\' || Character::is_name_start($first)) {
            return true;
        }
        if ($first === '#' && $this->scanner->peek_char(1) === '{') {
            return true;
        }
        if ($first !== '-') {
            return false;
        }
        $second = $this->scanner->peek_char(1);
        if ($second === null) {
            return false;
        }
        if ($second === '#') {
            return $this->scanner->peek_char(2) === '{';
        }
        return $second === '\\' || $second === '-' || Character::is_name_start($second);
    }
    /**
     * Returns whether the scanner is immediately before a sequence of characters
     * that could be part of an CSS identifier body.
     *
     * The identifier body may include interpolation.
     */
    private function looking_at_interpolated_identifier_body(): bool
    {
        $first = $this->scanner->peek_char();
        if ($first === null) {
            return false;
        }
        if ($first === '\\' || Character::is_name($first)) {
            return true;
        }
        return $first === '#' && $this->scanner->peek_char(1) === '{';
    }
    /**
     * Returns whether the scanner is immediately before a SassScript expression.
     */
    private function looking_at_expression(): bool
    {
        $character = $this->scanner->peek_char();
        if ($character === null) {
            return false;
        }
        if ($character === '.') {
            return $this->scanner->peek_char(1) !== '.';
        }
        if ($character === '!') {
            $next = $this->scanner->peek_char(1);
            return $next === null || $next === 'i' || $next === 'I' || Character::is_whitespace($next);
        }
        if ($character === '(') {
            return true;
        }
        if ($character === '/') {
            return true;
        }
        if ($character === '[') {
            return true;
        }
        if ($character === "'") {
            return true;
        }
        if ($character === '"') {
            return true;
        }
        if ($character === '#') {
            return true;
        }
        if ($character === '+') {
            return true;
        }
        if ($character === '-') {
            return true;
        }
        if ($character === '\\') {
            return true;
        }
        if ($character === '$') {
            return true;
        }
        if ($character === '&') {
            return true;
        }
        if (Character::is_name_start($character)) {
            return true;
        }
        return Character::is_digit($character);
    }
    /**
     * Consumes a block of $child statements and passes them, as well as the
     * span from $start to the end of the child block, to $create.
     *
     * @template T
     * @param callable(): Statement $child
     * @param callable(Statement[], FileSpan): T $create
     * @return T
     *
     * @param-immediately-invoked-callable $child
     * @param-immediately-invoked-callable $create
     */
    private function with_children(callable $child, int $start, callable $create)
    {
        $children = $this->children($child);
        $result = $create($children, $this->scanner->span_from($start));
        $this->whitespace_without_comments();
        return $result;
    }
    /**
     * Like {@see identifier}, but rejects identifiers that begin with `_` or `-`.
     */
    private function public_identifier(): string
    {
        $start = $this->scanner->get_position();
        $result = $this->identifier();
        $this->assert_public($result, fn(): \Source_Span\File_Span => $this->scanner->span_from($start));
        return $result;
    }
    /**
     * Throws an error if $identifier isn't public.
     *
     * Calls $span to provide the span for an error if one occurs.
     *
     * @param callable(): FileSpan $span
     *
     * @param-immediately-invoked-callable $span
     */
    private function assert_public(string $identifier, callable $span): void
    {
        if (!Character::is_private($identifier)) {
            return;
        }
        $this->error("Private members can't be accessed from outside their modules.", $span());
    }
    /**
     * Adds $expression to $buffer, or if it's an unquoted string adds the
     * interpolation it contains instead.
     */
    private function add_or_inject(Interpolation_Buffer $buffer, Expression $expression): void
    {
        if ($expression instanceof String_Expression && !$expression->has_quotes()) {
            $buffer->add_interpolation($expression->get_text());
        } else {
            $buffer->add($expression);
        }
    }
    /**
     * Whether this is parsing the indented syntax.
     */
    abstract protected function is_indented(): bool;
    /**
     * Whether this is a plain CSS stylesheet.
     */
    protected function is_plain_css(): bool
    {
        return false;
    }
    /**
     * The indentation level at the current scanner position.
     *
     * This value isn't used directly by StylesheetParser; it's just passed to
     * {@see scanElse}.
     */
    abstract protected function get_current_indentation(): int;
    /**
     * Parses and returns a selector used in a style rule.
     */
    abstract protected function style_rule_selector(): Interpolation;
    /**
     * Asserts that the scanner is positioned before a statement separator, or at
     * the end of a list of statements.
     *
     * If the name of the parent rule is passed, it's used for error reporting.
     *
     * This consumes whitespace, but nothing else, including comments.
     *
     * @throws FormatException
     */
    abstract protected function expect_statement_separator(?string $name = null): void;
    /**
     * Whether the scanner is positioned at the end of a statement.
     */
    abstract protected function at_end_of_statement(): bool;
    /**
     * Whether the scanner is positioned before a block of children that can be
     * parsed with {@see children}.
     */
    abstract protected function looking_at_children(): bool;
    /**
     * Tries to scan an `@else` rule after an `@if` block, and returns whether that succeeded.
     *
     * This should just scan the rule name, not anything afterwards.
     * $ifIndentation is the result of {@see getCurrentIndentation} from before the
     * corresponding `@if` was parsed.
     */
    abstract protected function scan_else(int $if_indentation): bool;
    /**
     * Consumes a block of child statements.
     *
     * Unlike most production consumers, this does *not* consume trailing
     * whitespace. This is necessary to ensure that the source span for the
     * parent rule doesn't cover whitespace after the rule.
     *
     * @param callable(): Statement $child
     *
     * @return Statement[]
     *
     * @param-immediately-invoked-callable $child
     */
    abstract protected function children(callable $child): array;
    /**
     * Consumes top-level statements.
     *
     * The $statement callback may return `null`, indicating that a statement
     * was consumed that shouldn't be added to the AST.
     *
     * @param callable(): ?Statement $statement
     *
     * @return Statement[]
     *
     * @param-immediately-invoked-callable $statement
     */
    abstract protected function statements(callable $statement): array;
}