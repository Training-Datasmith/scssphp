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

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Ast\Selector\Attribute_Operator;
use Scss_Php\Scss_Php\Ast\Selector\Attribute_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Class_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Combinator;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector_Component;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Id_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Parent_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Qualified_Name;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Type_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Universal_Selector;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Character;
/**
 * A parser for selectors.
 *
 * @internal
 */
final class Selector_Parser extends Parser
{
    /**
     * Pseudo-class selectors that take unadorned selectors as arguments.
     */
    private const SELECTOR_PSEUDO_CLASSES = ['not', 'is', 'matches', 'where', 'current', 'any', 'has', 'host', 'host-context'];
    /**
     * Pseudo-element selectors that take unadorned selectors as arguments.
     */
    private const SELECTOR_PSEUDO_ELEMENTS = ['slotted'];
    /**
     * Creates a parser that parses CSS selectors.
     *
     * If $allowParent is `false`, this will throw a @see SassFormatException} if
     * the selector includes the parent selector `&`.
     *
     * If $plainCss is `true`, this will parse the selector as a plain CSS
     * selector rather than a Sass selector.
     */
    public function __construct(
        string $contents,
        ?Logger_Interface $logger = null,
        ?Uri_Interface $url = null,
        private readonly bool $allow_parent = true,
        ?Interpolation_Map $interpolation_map = null,
        /**
         * Whether to parse the selector as plain CSS.
         */
        private readonly bool $plain_css = false
    )
    {
        parent::__construct($contents, $logger, $url, $interpolation_map);
    }
    /**
     * @throws SassFormatException
     */
    public function parse(): Selector_List
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Selector\Selector_List {
            $selector = $this->selector_list();
            if (!$this->scanner->is_done()) {
                $this->scanner->error('expected selector.');
            }
            return $selector;
        });
    }
    public function parse_complex_selector(): Complex_Selector
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector {
            $complex = $this->complex_selector();
            if (!$this->scanner->is_done()) {
                $this->scanner->error('expected selector.');
            }
            return $complex;
        });
    }
    public function parse_compound_selector(): Compound_Selector
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Selector\Compound_Selector {
            $compound = $this->compound_selector();
            if (!$this->scanner->is_done()) {
                $this->scanner->error('expected selector.');
            }
            return $compound;
        });
    }
    public function parse_simple_selector(): Simple_Selector
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Selector\Simple_Selector {
            $simple = $this->simple_selector();
            if (!$this->scanner->is_done()) {
                $this->scanner->error('unexpected token.');
            }
            return $simple;
        });
    }
    /**
     * Consumes a selector list.
     */
    private function selector_list(): Selector_List
    {
        $start = $this->scanner->get_position();
        $previous_line = $this->scanner->get_line();
        $components = [$this->complex_selector()];
        $this->whitespace();
        while ($this->scanner->scan_char(',')) {
            $this->whitespace();
            $next = $this->scanner->peek_char();
            if ($next === ',') {
                continue;
            }
            if ($this->scanner->is_done()) {
                break;
            }
            $line_break = $this->scanner->get_line() !== $previous_line;
            if ($line_break) {
                $previous_line = $this->scanner->get_line();
            }
            $components[] = $this->complex_selector($line_break);
        }
        return new Selector_List($components, $this->span_from($start));
    }
    /**
     * Consumes a complex selector.
     *
     * If $lineBreak is `true`, that indicates that there was a line break
     * before this selector.
     */
    private function complex_selector(bool $line_break = false): Complex_Selector
    {
        $start = $this->scanner->get_position();
        $component_start = $this->scanner->get_position();
        $last_compound = null;
        /** @var list<CssValue<Combinator>> $combinators */
        $combinators = [];
        $initial_combinators = null;
        $components = [];
        while (true) {
            $this->whitespace();
            $next = $this->scanner->peek_char();
            switch ($next) {
                case '+':
                    $combinator_start = $this->scanner->get_position();
                    $this->scanner->read_char();
                    $combinators[] = new Css_Value(Combinator::NEXT_SIBLING, $this->span_from($combinator_start));
                    break;
                case '>':
                    $combinator_start = $this->scanner->get_position();
                    $this->scanner->read_char();
                    $combinators[] = new Css_Value(Combinator::CHILD, $this->span_from($combinator_start));
                    break;
                case '~':
                    $combinator_start = $this->scanner->get_position();
                    $this->scanner->read_char();
                    $combinators[] = new Css_Value(Combinator::FOLLOWING_SIBLING, $this->span_from($combinator_start));
                    break;
                default:
                    if ($next === null || !\in_array($next, ['[', '.', '#', '%', ':', '&', '*', '|'], true) && !$this->looking_at_identifier()) {
                        break 2;
                    }
                    if ($last_compound !== null) {
                        $components[] = new Complex_Selector_Component($last_compound, $combinators, $this->span_from($component_start));
                    } elseif (\count($combinators) !== 0) {
                        \assert($initial_combinators === null);
                        $initial_combinators = $combinators;
                        $component_start = $this->scanner->get_position();
                    }
                    $last_compound = $this->compound_selector();
                    $combinators = [];
                    if ($this->scanner->peek_char() === '&') {
                        $this->scanner->error('"&" may only used at the beginning of a compound selector.');
                    }
                    break;
            }
        }
        if (\count($combinators) > 0 && $this->plain_css) {
            $this->scanner->error('expected selector.');
        }
        if ($last_compound !== null) {
            $components[] = new Complex_Selector_Component($last_compound, $combinators, $this->span_from($component_start));
        } elseif (\count($combinators) !== 0) {
            $initial_combinators = $combinators;
        } else {
            $this->scanner->error('expected selector.');
        }
        return new Complex_Selector($initial_combinators ?? [], $components, $this->span_from($start), $line_break);
    }
    /**
     * Consumes a compound selector.
     */
    private function compound_selector(): Compound_Selector
    {
        $start = $this->scanner->get_position();
        $components = [$this->simple_selector()];
        while ($this->is_simple_selector_start($this->scanner->peek_char())) {
            $components[] = $this->simple_selector(false);
        }
        return new Compound_Selector($components, $this->span_from($start));
    }
    /**
     * Consumes a simple selector.
     *
     * If $allowParent is passed, it controls whether the parent selector `&` is
     * allowed. Otherwise, it defaults to {@see allowParent}.
     */
    private function simple_selector(?bool $allow_parent = null): Simple_Selector
    {
        $start = $this->scanner->get_position();
        $allow_parent ??= $this->allow_parent;
        switch ($this->scanner->peek_char()) {
            case '[':
                return $this->attribute_selector();
            case '.':
                return $this->class_selector();
            case '#':
                return $this->id_selector();
            case '%':
                $selector = $this->placeholder_selector();
                if ($this->plain_css) {
                    $this->error("Placeholder selectors aren't allowed in plain CSS.", $this->scanner->span_from($start));
                }
                return $selector;
            case ':':
                return $this->pseudo_selector();
            case '&':
                $selector = $this->parent_selector();
                if (!$allow_parent) {
                    $this->error("Parent selectors aren't allowed here.", $this->scanner->span_from($start));
                }
                return $selector;
            default:
                return $this->type_or_universal_selector();
        }
    }
    /**
     * Consumes an attribute selector.
     */
    private function attribute_selector(): Attribute_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('[');
        $this->whitespace();
        $name = $this->attribute_name();
        $this->whitespace();
        if ($this->scanner->scan_char(']')) {
            return Attribute_Selector::create($name, $this->span_from($start));
        }
        $operator = $this->attribute_operator();
        $this->whitespace();
        $next = $this->scanner->peek_char();
        $value = $next === "'" || $next === '"' ? $this->string() : $this->identifier();
        $this->whitespace();
        $next = $this->scanner->peek_char();
        $modifier = $next !== null && Character::is_alphabetic($next) ? $this->scanner->read_char() : null;
        $this->scanner->expect_char(']');
        return Attribute_Selector::with_operator($name, $operator, $value, $this->span_from($start), $modifier);
    }
    /**
     * Consumes a qualified name as part of an attribute selector.
     */
    private function attribute_name(): Qualified_Name
    {
        if ($this->scanner->scan_char('*')) {
            $this->scanner->expect_char('|');
            return new Qualified_Name($this->identifier(), '*');
        }
        if ($this->scanner->scan_char('|')) {
            return new Qualified_Name($this->identifier(), '');
        }
        $name_or_namespace = $this->identifier();
        if ($this->scanner->peek_char() !== '|' || $this->scanner->peek_char(1) === '=') {
            return new Qualified_Name($name_or_namespace);
        }
        $this->scanner->read_char();
        return new Qualified_Name($this->identifier(), $name_or_namespace);
    }
    /**
     * Consumes an attribute selector's operator.
     */
    private function attribute_operator(): Attribute_Operator
    {
        $start = $this->scanner->get_position();
        switch ($this->scanner->read_char()) {
            case '=':
                return Attribute_Operator::EQUAL;
            case '~':
                $this->scanner->expect_char('=');
                return Attribute_Operator::INCLUDE;
            case '|':
                $this->scanner->expect_char('=');
                return Attribute_Operator::DASH;
            case '^':
                $this->scanner->expect_char('=');
                return Attribute_Operator::PREFIX;
            case '$':
                $this->scanner->expect_char('=');
                return Attribute_Operator::SUFFIX;
            case '*':
                $this->scanner->expect_char('=');
                return Attribute_Operator::SUBSTRING;
            default:
                $this->scanner->error('Expected "]".', $start);
        }
    }
    /**
     * Consumes a class selector.
     */
    private function class_selector(): Class_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('.');
        $name = $this->identifier();
        return new Class_Selector($name, $this->span_from($start));
    }
    /**
     * Consumes an ID selector.
     */
    private function id_selector(): Id_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('#');
        $name = $this->identifier();
        return new Id_Selector($name, $this->span_from($start));
    }
    /**
     * Consumes a placeholder selector.
     */
    private function placeholder_selector(): Placeholder_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('%');
        $name = $this->identifier();
        return new Placeholder_Selector($name, $this->span_from($start));
    }
    /**
     * Consumes a parent selector.
     */
    private function parent_selector(): Parent_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char('&');
        $suffix = $this->looking_at_identifier_body() ? $this->identifier_body() : null;
        if ($this->plain_css && $suffix !== null) {
            $this->scanner->error("Parent selectors can't have suffixes in plain CSS.", $start, $this->scanner->get_position() - $start);
        }
        return new Parent_Selector($this->span_from($start), $suffix);
    }
    /**
     * Consumes a pseudo selector.
     */
    private function pseudo_selector(): Pseudo_Selector
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect_char(':');
        $element = $this->scanner->scan_char(':');
        $name = $this->identifier();
        if (!$this->scanner->scan_char('(')) {
            return new Pseudo_Selector($name, $this->span_from($start), $element);
        }
        $this->whitespace();
        $unvendored = Util::unvendor($name);
        $argument = null;
        $selector = null;
        if ($element) {
            if (\in_array($unvendored, self::SELECTOR_PSEUDO_ELEMENTS, true)) {
                $selector = $this->selector_list();
            } else {
                $argument = $this->declaration_value(true);
            }
        } elseif (\in_array($unvendored, self::SELECTOR_PSEUDO_CLASSES, true)) {
            $selector = $this->selector_list();
        } elseif ($unvendored === 'nth-child' || $unvendored === 'nth-last-child') {
            $argument = $this->a_n_plus_b();
            $this->whitespace();
            if (Character::is_whitespace($this->scanner->peek_char(-1)) && $this->scanner->peek_char() !== ')') {
                $this->expect_identifier('of');
                $argument .= ' of';
                $this->whitespace();
                $selector = $this->selector_list();
            }
        } else {
            $argument = rtrim($this->declaration_value(true));
        }
        $this->scanner->expect_char(')');
        return new Pseudo_Selector($name, $this->span_from($start), $element, $argument, $selector);
    }
    /**
     * Consumes an [`An+B` production][An+B] and returns its text.
     *
     * [An+B]: https://drafts.csswg.org/css-syntax-3/#anb-microsyntax
     */
    private function a_n_plus_b(): string
    {
        $buffer = '';
        switch ($this->scanner->peek_char()) {
            case 'e':
            case 'E':
                $this->expect_identifier('even');
                return 'even';
            case 'o':
            case 'O':
                $this->expect_identifier('odd');
                return 'odd';
            case '+':
            case '-':
                $buffer .= $this->scanner->read_char();
                break;
        }
        $first = $this->scanner->peek_char();
        if ($first !== null && Character::is_digit($first)) {
            while (Character::is_digit($this->scanner->peek_char())) {
                $buffer .= $this->scanner->read_char();
            }
            $this->whitespace();
            if (!$this->scan_ident_char('n')) {
                return $buffer;
            }
        } else {
            $this->expect_ident_char('n');
        }
        $buffer .= 'n';
        $this->whitespace();
        $next = $this->scanner->peek_char();
        if ($next !== '+' && $next !== '-') {
            return $buffer;
        }
        $buffer .= $this->scanner->read_char();
        $this->whitespace();
        $last = $this->scanner->peek_char();
        if ($last === null || !Character::is_digit($last)) {
            $this->scanner->error('Expected a number.');
        }
        while (Character::is_digit($this->scanner->peek_char())) {
            $buffer .= $this->scanner->read_char();
        }
        return $buffer;
    }
    /**
     * Consumes a type selector or a universal selector.
     *
     * These are combined because either one could start with `*`.
     */
    private function type_or_universal_selector(): Simple_Selector
    {
        $start = $this->scanner->get_position();
        $first = $this->scanner->peek_char();
        if ($first === '*') {
            $this->scanner->read_char();
            if (!$this->scanner->scan_char('|')) {
                return new Universal_Selector($this->span_from($start));
            }
            if ($this->scanner->scan_char('*')) {
                return new Universal_Selector($this->span_from($start), '*');
            }
            return new Type_Selector(new Qualified_Name($this->identifier(), '*'), $this->span_from($start));
        }
        if ($first === '|') {
            $this->scanner->read_char();
            if ($this->scanner->scan_char('*')) {
                return new Universal_Selector($this->span_from($start), '');
            }
            return new Type_Selector(new Qualified_Name($this->identifier(), ''), $this->span_from($start));
        }
        $name_or_namespace = $this->identifier();
        if (!$this->scanner->scan_char('|')) {
            return new Type_Selector(new Qualified_Name($name_or_namespace), $this->span_from($start));
        }
        if ($this->scanner->scan_char('*')) {
            return new Universal_Selector($this->span_from($start), $name_or_namespace);
        }
        return new Type_Selector(new Qualified_Name($this->identifier(), $name_or_namespace), $this->span_from($start));
    }
    /**
     *  Returns whether $character can start a simple selector in the middle of a compound selector.
     */
    private function is_simple_selector_start(?string $character): bool
    {
        return match ($character) {
            '*', '[', '.', '#', '%', ':' => true,
            '&' => $this->plain_css,
            default => false,
        };
    }
}