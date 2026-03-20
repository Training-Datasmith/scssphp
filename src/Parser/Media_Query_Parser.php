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

use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
/**
 * A parser for `@media` queries.
 *
 * @internal
 */
final class Media_Query_Parser extends Parser
{
    /**
     * @return list<CssMediaQuery>
     *
     * @throws SassFormatException when parsing fails
     */
    public function parse(): array
    {
        return $this->wrap_span_format_exception(function (): array {
            $queries = [];
            do {
                $this->whitespace();
                $queries[] = $this->media_query();
                $this->whitespace();
            } while ($this->scanner->scan_char(','));
            $this->scanner->expect_done();
            return $queries;
        });
    }
    /**
     * Consumes a single media query.
     */
    private function media_query(): Css_Media_Query
    {
        if ($this->scanner->peek_char() === '(') {
            $conditions = [$this->media_in_parens()];
            $this->whitespace();
            $conjunction = true;
            if ($this->scan_identifier('and')) {
                $this->expect_whitespace();
                $conditions = array_merge($conditions, $this->media_logic_sequence('and'));
            } elseif ($this->scan_identifier('or')) {
                $this->expect_whitespace();
                $conjunction = false;
                $conditions = array_merge($conditions, $this->media_logic_sequence('or'));
            }
            return Css_Media_Query::condition($conditions, $conjunction);
        }
        $modifier = null;
        $type = null;
        $identifier1 = $this->identifier();
        if (strtolower($identifier1) === 'not') {
            $this->expect_whitespace();
            if (!$this->looking_at_identifier()) {
                // For example, "@media not (...) {"
                return Css_Media_Query::condition(['(not ' . $this->media_in_parens() . ')']);
            }
        }
        $this->whitespace();
        if (!$this->looking_at_identifier()) {
            // For example, "@media screen {"
            return Css_Media_Query::type($identifier1);
        }
        $identifier2 = $this->identifier();
        if (strtolower($identifier2) === 'and') {
            $this->expect_whitespace();
            // For example, "@media screen and ..."
            $type = $identifier1;
        } else {
            $this->whitespace();
            $modifier = $identifier1;
            $type = $identifier2;
            if ($this->scan_identifier('and')) {
                // For example, "@media only screen and ..."
                $this->expect_whitespace();
            } else {
                // For example, "@media only screen {"
                return Css_Media_Query::type($type, $modifier);
            }
        }
        // We've consumed either `IDENTIFIER "and"` or
        // `IDENTIFIER IDENTIFIER "and"`.
        if ($this->scan_identifier('not')) {
            $this->expect_whitespace();
            // For example, "@media screen and not (...) {"
            return Css_Media_Query::type($type, $modifier, ['(not ' . $this->media_in_parens() . ')']);
        }
        return Css_Media_Query::type($type, $modifier, $this->media_logic_sequence('and'));
    }
    /**
     * Consumes one or more `<media-in-parens>` expressions separated by
     * $operator and returns them.
     *
     * @return list<string>
     */
    private function media_logic_sequence(string $operator): array
    {
        $result = [];
        while (true) {
            $result[] = $this->media_in_parens();
            $this->whitespace();
            if (!$this->scan_identifier($operator)) {
                return $result;
            }
            $this->expect_whitespace();
        }
    }
    /**
     * Consumes a `<media-in-parens>` expression and returns it, parentheses
     * included.
     */
    private function media_in_parens(): string
    {
        $this->scanner->expect_char('(', 'media condition in parentheses');
        $result = '(' . $this->declaration_value() . ')';
        $this->scanner->expect_char(')');
        return $result;
    }
}