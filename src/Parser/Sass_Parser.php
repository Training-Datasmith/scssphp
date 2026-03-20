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
use Scss_Php\Scss_Php\Ast\Sass\Import;
use Scss_Php\Scss_Php\Ast\Sass\Import\Dynamic_Import;
use Scss_Php\Scss_Php\Ast\Sass\Import\Static_Import;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Loud_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Value\Sass_String;
/**
 * A parser for the indented syntax.
 *
 * @internal
 */
final class Sass_Parser extends Stylesheet_Parser
{
    private int $current_indentation = 0;
    /**
     * The indentation level of the next source line after the scanner's
     * position, or `null` if that hasn't been computed yet.
     *
     * A source line is any line that's not entirely whitespace.
     */
    private ?int $next_indentation = null;
    /**
     * The beginning of the next source line after the scanner's position, or
     * `null` if the next indentation hasn't been computed yet.
     *
     * A source line is any line that's not entirely whitespace.
     */
    private ?int $next_indentation_end = null;
    /**
     * Whether the document is indented using spaces or tabs.
     *
     * If this is `true`, the document is indented using spaces. If it's `false`,
     * the document is indented using tabs. If it's `null`, we haven't yet seen
     * the indentation character used by the document.
     */
    private ?bool $spaces = null;
    public function get_current_indentation(): int
    {
        return $this->current_indentation;
    }
    protected function is_indented(): bool
    {
        return true;
    }
    protected function style_rule_selector(): Interpolation
    {
        $start = $this->scanner->get_position();
        $buffer = new Interpolation_Buffer();
        do {
            $buffer->add_interpolation($this->almost_any_value(omitComments: true));
            $buffer->write("\n");
        } while (str_ends_with(rtrim($buffer->get_trailing_string()), ',') && $this->scan_char_if(fn(?string $char): bool => Character::is_newline($char)));
        return $buffer->build_interpolation($this->scanner->span_from($start));
    }
    protected function expect_statement_separator(?string $name = null): void
    {
        if (!$this->at_end_of_statement()) {
            $this->expect_newline();
        }
        if ($this->peek_indentation() <= $this->current_indentation) {
            return;
        }
        \assert($this->next_indentation_end !== null);
        $this->scanner->error(\sprintf('Nothing may be indented %s.', $name === null ? 'here' : "beneath a {$name}"), $this->next_indentation_end);
    }
    protected function at_end_of_statement(): bool
    {
        $next_char = $this->scanner->peek_char();
        return $next_char === null || Character::is_newline($next_char);
    }
    protected function looking_at_children(): bool
    {
        return $this->at_end_of_statement() && $this->peek_indentation() > $this->current_indentation;
    }
    protected function import_argument(): Import
    {
        switch ($this->scanner->peek_char()) {
            case 'u':
            case 'U':
                $start = $this->scanner->get_position();
                if ($this->scan_identifier('url')) {
                    if ($this->scanner->scan_char('(')) {
                        $this->scanner->set_position($start);
                        return parent::import_argument();
                    }
                    $this->scanner->set_position($start);
                }
                break;
            case "'":
            case '"':
                return parent::import_argument();
        }
        $start = $this->scanner->get_position();
        $next = $this->scanner->peek_char();
        while ($next !== null && $next !== ',' && $next !== ';' && !Character::is_newline($next)) {
            $this->scanner->read_utf8char();
            $next = $this->scanner->peek_char();
        }
        $url = $this->scanner->substring($start);
        $span = $this->scanner->span_from($start);
        if ($this->is_plain_import_url($url)) {
            // Serialize $url as a Sass string because StaticImport expects it to
            // include quotes.
            return new Static_Import(new Interpolation([(string) new Sass_String($url)], $span), $span);
        }
        try {
            return new Dynamic_Import($this->parse_import_url($url), $span);
        } catch (Syntax_Error $e) {
            $this->error('Invalid URL: ' . $e->get_message(), $span, $e);
        }
    }
    protected function scan_else(int $if_indentation): bool
    {
        if ($this->peek_indentation() !== $if_indentation) {
            return false;
        }
        $start = $this->scanner->get_position();
        $start_indentation = $this->current_indentation;
        $start_next_indentation = $this->next_indentation;
        $start_next_indentation_end = $this->next_indentation_end;
        $this->read_indentation();
        if ($this->scanner->scan_char('@') && $this->scan_identifier('else')) {
            return true;
        }
        $this->scanner->set_position($start);
        $this->current_indentation = $start_indentation;
        $this->next_indentation = $start_next_indentation;
        $this->next_indentation_end = $start_next_indentation_end;
        return false;
    }
    protected function children(callable $child): array
    {
        $children = [];
        $this->while_indented_lower(function () use ($child, &$children): void {
            $parsed_child = $this->child($child);
            if ($parsed_child !== null) {
                $children[] = $parsed_child;
            }
        });
        return $children;
    }
    protected function statements(callable $statement): array
    {
        $next = $this->scanner->peek_char();
        if ($next === "\t" || $next === ' ') {
            $this->scanner->error('Indenting at the beginning of the document is illegal.', 0, $this->scanner->get_position());
        }
        $statements = [];
        while (!$this->scanner->is_done()) {
            $child = $this->child($statement);
            if ($child !== null) {
                $statements[] = $child;
            }
            $indentation = $this->read_indentation();
            \assert($indentation === 0);
        }
        return $statements;
    }
    /**
     * Consumes a child of the current statement.
     *
     * This consumes children that are allowed at all levels of the document; the
     * $child parameter is called to consume any children that are specifically
     * allowed in the caller's context.
     *
     * @param callable(): (Statement|null) $child
     */
    private function child(callable $child): ?Statement
    {
        return match ($this->scanner->peek_char()) {
            // Ignore empty lines.
            "\r", "\n", "\f" => null,
            '$' => $this->variable_declaration_without_namespace(),
            '/' => match ($this->scanner->peek_char(1)) {
                '/' => $this->silent_comment_statement(),
                '*' => $this->loud_comment_statement(),
                default => $child(),
            },
            default => $child(),
        };
    }
    /**
     * Consumes an indented-style silent comment.
     */
    private function silent_comment_statement(): Silent_Comment
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect('//');
        $buffer = '';
        $parent_indentation = $this->current_indentation;
        do {
            $comment_prefix = $this->scanner->scan_char('/') ? '///' : '//';
            while (true) {
                $buffer .= $comment_prefix;
                // Skip the initial characters because we're already writing the
                // slashes.
                for ($i = \strlen($comment_prefix); $i < $this->current_indentation - $parent_indentation; $i++) {
                    $buffer .= ' ';
                }
                while (!$this->scanner->is_done() && !Character::is_newline($this->scanner->peek_char())) {
                    $buffer .= $this->scanner->read_utf8char();
                }
                $buffer .= "\n";
                if ($this->peek_indentation() < $parent_indentation) {
                    break 2;
                }
                if ($this->peek_indentation() === $parent_indentation) {
                    // Look ahead to the next line to see if it starts another comment.
                    if ($this->scanner->peek_char(1 + $parent_indentation) === '/' && $this->scanner->peek_char(2 + $parent_indentation) === '/') {
                        $this->read_indentation();
                    }
                    break;
                }
                $this->read_indentation();
            }
        } while ($this->scanner->scan('//'));
        return $this->last_silent_comment = new Silent_Comment($buffer, $this->scanner->span_from($start));
    }
    /**
     * Consumes an indented-style loud context.
     */
    private function loud_comment_statement(): Loud_Comment
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect('/*');
        $first = true;
        $buffer = new Interpolation_Buffer();
        $buffer->write('/*');
        $parent_indentation = $this->current_indentation;
        while (true) {
            if ($first) {
                // If the first line is empty, ignore it.
                $beginning_of_comment = $this->scanner->get_position();
                $this->spaces();
                if (Character::is_newline($this->scanner->peek_char())) {
                    $this->read_indentation();
                    $buffer->write(' ');
                } else {
                    $buffer->write($this->scanner->substring($beginning_of_comment));
                }
            } else {
                $buffer->write("\n * ");
            }
            $first = false;
            for ($i = 3; $i < $this->current_indentation - $parent_indentation; $i++) {
                $buffer->write(' ');
            }
            while (!$this->scanner->is_done()) {
                switch ($this->scanner->peek_char()) {
                    case "\n":
                    case "\r":
                    case "\f":
                        break 2;
                    case '#':
                        if ($this->scanner->peek_char(1) === '{') {
                            $buffer->add($this->single_interpolation());
                        } else {
                            $buffer->write($this->scanner->read_char());
                        }
                        break;
                    default:
                        $buffer->write($this->scanner->read_utf8char());
                }
            }
            if ($this->peek_indentation() <= $parent_indentation) {
                break;
            }
            // Preserve empty lines.
            while ($this->looking_at_double_newline()) {
                $this->expect_newline();
                $buffer->write("\n *");
            }
            $this->read_indentation();
        }
        return new Loud_Comment($buffer->build_interpolation($this->scanner->span_from($start)));
    }
    protected function whitespace_without_comments(): void
    {
        // This overrides whitespace consumption so that it doesn't consume
        // newlines.
        while (!$this->scanner->is_done()) {
            $next = $this->scanner->peek_char();
            if ($next !== "\t" && $next !== ' ') {
                break;
            }
            $this->scanner->read_char();
        }
    }
    protected function loud_comment(): void
    {
        // This overrides loud comment consumption so that it doesn't consume
        // multi-line comments.
        $this->scanner->expect('/*');
        while (true) {
            $next = $this->scanner->read_utf8char();
            if (Character::is_newline($next)) {
                $this->scanner->error('expected */.');
            }
            if ($next !== '*') {
                continue;
            }
            do {
                $next = $this->scanner->read_utf8char();
            } while ($next === '*');
            if ($next === '/') {
                break;
            }
        }
    }
    /**
     * Expect and consume a single newline character.
     */
    private function expect_newline(): void
    {
        switch ($this->scanner->peek_char()) {
            case ';':
                $this->scanner->error("semicolons aren't allowed in the indented syntax.");
            // no break
            case "\r":
                $this->scanner->read_char();
                if ($this->scanner->peek_char() === "\n") {
                    $this->scanner->read_char();
                }
                break;
            case "\n":
            case "\f":
                $this->scanner->read_char();
                break;
            default:
                $this->scanner->error('expected newline.');
        }
    }
    /**
     * Returns whether the scanner is immediately before *two* newlines.
     */
    private function looking_at_double_newline(): bool
    {
        return match ($this->scanner->peek_char()) {
            "\r" => match ($this->scanner->peek_char(1)) {
                "\n" => Character::is_newline($this->scanner->peek_char(2)),
                "\r", "\f" => true,
                default => false,
            },
            "\n", "\f" => Character::is_newline($this->scanner->peek_char(1)),
            default => false,
        };
    }
    /**
     * As long as the scanner's position is indented beneath the starting line,
     * runs $body to consume the next statement.
     *
     * @param callable(): void $body
     */
    private function while_indented_lower(callable $body): void
    {
        $parent_indentation = $this->current_indentation;
        $child_indentation = null;
        while ($this->peek_indentation() > $parent_indentation) {
            $indentation = $this->read_indentation();
            $child_indentation ??= $indentation;
            if ($child_indentation !== $indentation) {
                $this->scanner->error("Inconsistent indentation, expected {$child_indentation} spaces.", $this->scanner->get_position() - $this->scanner->get_column(), $this->scanner->get_column());
            }
            $body();
        }
    }
    /**
     * Consumes indentation whitespace and returns the indentation level of the
     * next line.
     *
     * @phpstan-impure
     */
    private function read_indentation(): int
    {
        $current_indentation = $this->current_indentation = $this->next_indentation ??= $this->peek_indentation();
        \assert($this->next_indentation_end !== null);
        $this->scanner->set_position($this->next_indentation_end);
        $this->next_indentation = null;
        $this->next_indentation_end = null;
        return $current_indentation;
    }
    /**
     * Returns the indentation level of the next line.
     */
    private function peek_indentation(): int
    {
        if ($this->next_indentation !== null) {
            return $this->next_indentation;
        }
        if ($this->scanner->is_done()) {
            $this->next_indentation = 0;
            $this->next_indentation_end = $this->scanner->get_position();
            return 0;
        }
        $start = $this->scanner->get_position();
        do {
            $contains_tab = false;
            $contains_space = false;
            $next_indentation = 0;
            while (true) {
                switch ($this->scanner->peek_char()) {
                    case ' ':
                        $contains_space = true;
                        break;
                    case "\t":
                        $contains_tab = true;
                        break;
                    default:
                        break 2;
                }
                $next_indentation++;
                $this->scanner->read_char();
            }
            if ($this->scanner->is_done()) {
                $this->next_indentation = 0;
                $this->next_indentation_end = $this->scanner->get_position();
                $this->scanner->set_position($start);
                return 0;
            }
        } while ($this->scan_char_if(fn(?string $char): bool => Character::is_newline($char)));
        $this->check_indentation_consistency($contains_tab, $contains_space);
        $this->next_indentation = $next_indentation;
        if ($next_indentation > 0) {
            $this->spaces ??= $contains_space;
        }
        $this->next_indentation_end = $this->scanner->get_position();
        $this->scanner->set_position($start);
        return $next_indentation;
    }
    /**
     * Ensures that the document uses consistent characters for indentation.
     *
     * The $containsTab and $containsSpace parameters refer to a single line of
     * indentation that has just been parsed.
     */
    private function check_indentation_consistency(bool $contains_tab, bool $contains_space): void
    {
        if ($contains_tab) {
            if ($contains_space) {
                $this->scanner->error('Tabs and spaces may not be mixed.', $this->scanner->get_position() - $this->scanner->get_column(), $this->scanner->get_column());
            }
            if ($this->spaces === true) {
                $this->scanner->error('Expected spaces, was tabs.', $this->scanner->get_position() - $this->scanner->get_column(), $this->scanner->get_column());
            }
        } elseif ($contains_space && $this->spaces === false) {
            $this->scanner->error('Expected tabs, was spaces.', $this->scanner->get_position() - $this->scanner->get_column(), $this->scanner->get_column());
        }
    }
}