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

use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Loud_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Logger_Util;
/**
 * A parser for the CSS-compatible syntax.
 *
 * @internal
 */
class Scss_Parser extends Stylesheet_Parser
{
    protected function is_indented(): bool
    {
        return false;
    }
    protected function get_current_indentation(): int
    {
        return 0;
    }
    protected function style_rule_selector(): Interpolation
    {
        return $this->almost_any_value();
    }
    protected function expect_statement_separator(?string $name = null): void
    {
        $this->whitespace_without_comments();
        if ($this->scanner->is_done()) {
            return;
        }
        $next = $this->scanner->peek_char();
        if ($next === ';' || $next === '}') {
            return;
        }
        $this->scanner->expect_char(';');
    }
    protected function at_end_of_statement(): bool
    {
        $next = $this->scanner->peek_char();
        return $next === null || $next === ';' || $next === '}' || $next === '{';
    }
    protected function looking_at_children(): bool
    {
        return $this->scanner->peek_char() === '{';
    }
    protected function scan_else(int $if_indentation): bool
    {
        $start = $this->scanner->get_position();
        $this->whitespace();
        $before_at = $this->scanner->get_position();
        if ($this->scanner->scan_char('@')) {
            if ($this->scan_identifier('else', true)) {
                return true;
            }
            if ($this->scan_identifier('elseif', true)) {
                Logger_Util::warn_for_deprecation($this->logger, Deprecation::elseif, "@elseif is deprecated and will not be supported in future Sass versions.\n\nRecommendation: @else if", $this->scanner->span_from($before_at));
                $this->scanner->set_position($this->scanner->get_position() - 2);
                return true;
            }
        }
        $this->scanner->set_position($start);
        return false;
    }
    protected function children(callable $child): array
    {
        $this->scanner->expect_char('{');
        $this->whitespace_without_comments();
        $children = [];
        while (true) {
            switch ($this->scanner->peek_char()) {
                case '$':
                    $children[] = $this->variable_declaration_without_namespace();
                    break;
                case '/':
                    switch ($this->scanner->peek_char(1)) {
                        case '/':
                            $children[] = $this->silent_comment_statement();
                            $this->whitespace_without_comments();
                            break;
                        case '*':
                            $children[] = $this->loud_comment_statement();
                            $this->whitespace_without_comments();
                            break;
                        default:
                            $children[] = $child();
                            break;
                    }
                    break;
                case ';':
                    $this->scanner->read_char();
                    $this->whitespace_without_comments();
                    break;
                case '}':
                    $this->scanner->expect_char('}');
                    return $children;
                default:
                    $children[] = $child();
                    break;
            }
        }
    }
    protected function statements(callable $statement): array
    {
        $statements = [];
        $this->whitespace_without_comments();
        while (!$this->scanner->is_done()) {
            switch ($this->scanner->peek_char()) {
                case '$':
                    $statements[] = $this->variable_declaration_without_namespace();
                    break;
                case '/':
                    switch ($this->scanner->peek_char(1)) {
                        case '/':
                            $statements[] = $this->silent_comment_statement();
                            $this->whitespace_without_comments();
                            break;
                        case '*':
                            $statements[] = $this->loud_comment_statement();
                            $this->whitespace_without_comments();
                            break;
                        default:
                            $child = $statement();
                            if ($child !== null) {
                                $statements[] = $child;
                            }
                            break;
                    }
                    break;
                case ';':
                    $this->scanner->read_char();
                    $this->whitespace_without_comments();
                    break;
                default:
                    $child = $statement();
                    if ($child !== null) {
                        $statements[] = $child;
                    }
                    break;
            }
        }
        return $statements;
    }
    /**
     * Consumes a statement-level silent comment block.
     */
    private function silent_comment_statement(): Silent_Comment
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect('//');
        do {
            while (!$this->scanner->is_done() && !Character::is_newline($this->scanner->read_char())) {
                // Ignore the content of the comment
            }
            if ($this->scanner->is_done()) {
                break;
            }
            $this->spaces();
        } while ($this->scanner->scan('//'));
        if ($this->is_plain_css()) {
            $this->error('Silent comments aren\'t allowed in plain CSS.', $this->scanner->span_from($start));
        }
        $this->last_silent_comment = new Silent_Comment($this->scanner->substring($start), $this->scanner->span_from($start));
        return $this->last_silent_comment;
    }
    /**
     * Consumes a statement-level loud comment block.
     */
    private function loud_comment_statement(): Loud_Comment
    {
        $start = $this->scanner->get_position();
        $this->scanner->expect('/*');
        $buffer = new Interpolation_Buffer();
        $buffer->write('/*');
        while (true) {
            switch ($this->scanner->peek_char()) {
                case '#':
                    if ($this->scanner->peek_char(1) === '{') {
                        $buffer->add($this->single_interpolation());
                    } else {
                        $buffer->write($this->scanner->read_char());
                    }
                    break;
                case '*':
                    $buffer->write($this->scanner->read_char());
                    if ($this->scanner->peek_char() !== '/') {
                        break;
                    }
                    $buffer->write($this->scanner->read_char());
                    return new Loud_Comment($buffer->build_interpolation($this->scanner->span_from($start)));
                case "\r":
                    $this->scanner->read_char();
                    if ($this->scanner->peek_char() !== "\n") {
                        $buffer->write("\n");
                    }
                    break;
                case "\f":
                    $this->scanner->read_char();
                    $buffer->write("\n");
                    break;
                default:
                    $buffer->write($this->scanner->read_utf8char());
            }
        }
    }
}