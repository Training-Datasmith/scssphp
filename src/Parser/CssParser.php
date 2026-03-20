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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Parenthesized_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Import\Static_Import;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Import_Rule;
use Scss_Php\Scss_Php\Function\Function_Registry;
/**
 * A parser for imported CSS files.
 *
 * @internal
 */
final class Css_Parser extends Scss_Parser
{
    /**
     * Sass global functions which are shadowing a CSS function are allowed in CSS files.
     */
    private const CSS_ALLOWED_FUNCTIONS = ['rgb' => true, 'rgba' => true, 'hsl' => true, 'hsla' => true, 'grayscale' => true, 'invert' => true, 'alpha' => true, 'opacity' => true, 'saturate' => true, 'min' => true, 'max' => true, 'round' => true, 'abs' => true];
    protected function is_plain_css(): bool
    {
        return true;
    }
    protected function silent_comment(): bool
    {
        if ($this->in_expression()) {
            return false;
        }
        $start = $this->scanner->get_position();
        parent::silent_comment();
        $this->error("Silent comments aren't allowed in plain CSS.", $this->scanner->span_from($start));
    }
    protected function at_rule(callable $child, bool $root = false): Statement
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('@');
        $name = $this->interpolated_identifier();
        $this->whitespace();
        return match ($name->get_as_plain()) {
            'at-root', 'content', 'debug', 'each', 'error', 'extend', 'for', 'function', 'if', 'include', 'mixin', 'return', 'warn', 'while' => $this->forbidden_at_rule($start),
            'import' => $this->css_import_rule($start),
            'media' => $this->media_rule($start),
            '-moz-document' => $this->moz_document_rule($start, $name),
            'supports' => $this->supports_rule($start),
            default => $this->unknown_at_rule($start, $name),
        };
    }
    private function forbidden_at_rule(int $start): never
    {
        $this->almost_any_value();
        $this->error("This at-rule isn't allowed in plain CSS.", $this->scanner->span_from($start));
    }
    private function css_import_rule(int $start): Import_Rule
    {
        $url_start = $this->scanner->get_position();
        $next = $this->scanner->peek_char();
        if ($next === 'u' || $next === 'U') {
            $url = $this->dynamic_url();
        } else {
            $url = new String_Expression($this->interpolated_string()->as_interpolation(true));
        }
        $url_span = $this->scanner->span_from($url_start);
        $this->whitespace();
        $modifiers = $this->try_import_modifiers();
        $this->expect_statement_separator('@import rule');
        return new Import_Rule([new Static_Import(new Interpolation([$url], $url_span), $this->scanner->span_from($start), $modifiers)], $this->scanner->span_from($start));
    }
    protected function parentheses(): Expression
    {
        // Expressions are only allowed within calculations, but we verify this at
        // evaluation time.
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('(');
        $this->whitespace();
        $expression = $this->expression_until_comma();
        $this->scanner->expect_char(')');
        return new Parenthesized_Expression($expression, $this->scanner->span_from($start));
    }
    protected function identifier_like(): Expression
    {
        $start = $this->scanner->get_position();
        $identifier = $this->interpolated_identifier();
        $plain = $identifier->get_as_plain();
        assert($plain !== null);
        // CSS doesn't allow non-plain identifiers
        $lower = strtolower($plain);
        $special_function = $this->try_special_function($lower, $start);
        if ($special_function !== null) {
            return $special_function;
        }
        $before_arguments = $this->scanner->get_position();
        // `namespacedExpression()` is just here to throw a clearer error.
        if ($this->scanner->scan_char('.')) {
            return $this->namespaced_expression($plain, $start);
        }
        if (!$this->scanner->scan_char('(')) {
            return new String_Expression($identifier);
        }
        $allow_empty_second_arg = $lower === 'var';
        $arguments = [];
        if (!$this->scanner->scan_char(')')) {
            do {
                $this->whitespace();
                if ($allow_empty_second_arg && \count($arguments) === 1 && $this->scanner->peek_char() === ')') {
                    $arguments[] = String_Expression::plain('', $this->scanner->get_empty_span());
                    break;
                }
                $arguments[] = $this->expression_until_comma(true);
                $this->whitespace();
            } while ($this->scanner->scan_char(','));
            $this->scanner->expect_char(')');
        }
        if ($plain === 'if' || !isset(self::CSS_ALLOWED_FUNCTIONS[$plain]) && Function_Registry::is_builtin_function($plain)) {
            $this->error("This function isn't allowed in plain CSS.", $this->scanner->span_from($start));
        }
        return new Function_Expression($plain, new Argument_Invocation($arguments, [], $this->scanner->span_from($before_arguments)), $this->scanner->span_from($start));
    }
    protected function namespaced_expression(string $namespace, int $start): Expression
    {
        $expression = parent::namespaced_expression($namespace, $start);
        $this->error("Module namespaces aren't allowed in plain CSS.", $expression->get_span());
    }
}