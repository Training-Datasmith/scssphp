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

use Scss_Php\Scss_Php\Ast\Sass\At_Root_Query;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
/**
 * A parser for `@at-root` queries.
 *
 * @internal
 */
final class At_Root_Query_Parser extends Parser
{
    /**
     * @throws SassFormatException
     */
    public function parse(): At_Root_Query
    {
        return $this->wrap_span_format_exception(function (): \Scss_Php\Scss_Php\Ast\Sass\At_Root_Query {
            $this->scanner->expect_char('(');
            $this->whitespace();
            $include = $this->scan_identifier('with');
            if (!$include) {
                $this->expect_identifier('without', '"with" or "without"');
            }
            $this->whitespace();
            $this->scanner->expect_char(':');
            $this->whitespace();
            $at_rules = [];
            do {
                $at_rules[] = strtolower($this->identifier());
                $this->whitespace();
            } while ($this->looking_at_identifier());
            $this->scanner->expect_char(')');
            $this->scanner->expect_done();
            return At_Root_Query::create($at_rules, $include);
        });
    }
}