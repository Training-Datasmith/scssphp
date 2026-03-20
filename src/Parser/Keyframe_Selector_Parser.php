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

use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Util\Character;
/**
 * A parser for `@keyframes` block selectors.
 *
 * @internal
 */
final class Keyframe_Selector_Parser extends Parser
{
    /**
     * @return list<string>
     *
     * @throws SassFormatException
     */
    public function parse(): array
    {
        return $this->wrap_span_format_exception(function (): array {
            $selectors = [];
            do {
                $this->whitespace();
                if ($this->looking_at_identifier()) {
                    if ($this->scan_identifier('from')) {
                        $selectors[] = 'from';
                    } else {
                        $this->expect_identifier('to', '"to" or "from"');
                        $selectors[] = 'to';
                    }
                } else {
                    $selectors[] = $this->percentage();
                }
                $this->whitespace();
            } while ($this->scanner->scan_char(','));
            $this->scanner->expect_done();
            return $selectors;
        });
    }
    private function percentage(): string
    {
        $buffer = '';
        if ($this->scanner->scan_char('+')) {
            $buffer .= '+';
        }
        $second = $this->scanner->peek_char();
        if (!Character::is_digit($second) && $second !== '.') {
            $this->scanner->error('Expected number.');
        }
        while (Character::is_digit($this->scanner->peek_char())) {
            $buffer .= $this->scanner->read_char();
        }
        if ($this->scanner->peek_char() === '.') {
            $buffer .= $this->scanner->read_char();
            while (Character::is_digit($this->scanner->peek_char())) {
                $buffer .= $this->scanner->read_char();
            }
        }
        if ($this->scan_ident_char('e')) {
            $buffer .= 'e';
            $next = $this->scanner->peek_char();
            if ($next === '+' || $next === '-') {
                $buffer .= $this->scanner->read_char();
            }
            if (!Character::is_digit($this->scanner->peek_char())) {
                $this->scanner->error('Expected digit.');
            }
            while (Character::is_digit($this->scanner->peek_char())) {
                $buffer .= $this->scanner->read_char();
            }
        }
        $this->scanner->expect_char('%');
        return $buffer . '%';
    }
}