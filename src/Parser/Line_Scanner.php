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

/**
 * A subclass of {@see StringScanner} that tracks line and column information.
 *
 * @internal
 */
final class Line_Scanner extends String_Scanner
{
    private int $line = 0;
    private int $column = 0;
    public function get_line(): int
    {
        return $this->line;
    }
    public function get_column(): int
    {
        return $this->column;
    }
    /**
     * Whether the current position is between a CR character and an LF
     * character.
     */
    private function between_crlf(): bool
    {
        return $this->peek_char(-1) === "\r" && $this->peek_char() === "\n";
    }
    public function set_position(int $position): void
    {
        $new_position = $position;
        $old_position = $this->get_position();
        parent::set_position($position);
        if ($new_position > $old_position) {
            $newlines = $this->newlines_in($this->substring($old_position, $new_position));
            $this->line += \count($newlines);
            if ($newlines === []) {
                $this->column += $new_position - $old_position;
            } else {
                $last = $newlines[\count($newlines) - 1];
                $end = $last[1] + \strlen($last[0]);
                $this->column = $new_position - $end;
            }
        } else {
            $newlines = $this->newlines_in($this->substring($new_position, $old_position));
            if ($this->between_crlf()) {
                array_pop($newlines);
            }
            $this->line -= \count($newlines);
            if ($newlines === []) {
                $this->column -= $old_position - $new_position;
            } else {
                $last_crlf_position = strrpos($this->get_string(), "\r\n", $new_position);
                if ($last_crlf_position === false) {
                    $last_crlf_position = -1;
                }
                $last_lf_position = strrpos($this->get_string(), "\n", $new_position);
                if ($last_lf_position === false) {
                    $last_lf_position = -1;
                }
                $last_new_line_position = max($last_crlf_position, $last_lf_position);
                $this->column = $new_position - $last_new_line_position - 1;
            }
        }
    }
    /**
     * @phpstan-impure
     */
    public function scan_char(string $char): bool
    {
        if (!parent::scan_char($char)) {
            return false;
        }
        $this->adjust_line_and_column($char);
        return true;
    }
    /**
     * @phpstan-impure
     */
    public function read_char(): string
    {
        $character = parent::read_char();
        $this->adjust_line_and_column($character);
        return $character;
    }
    /**
     * @phpstan-impure
     */
    public function read_utf8char(): string
    {
        $character = parent::read_utf8char();
        $this->adjust_line_and_column($character);
        return $character;
    }
    /**
     * Adjusts {@see line} and {@see column} after having consumed $character.
     */
    private function adjust_line_and_column(string $character): void
    {
        if ($character === "\n" || $character === "\r" && $this->peek_char() !== "\n") {
            $this->line += 1;
            $this->column = 0;
        } else {
            $this->column += \strlen($character);
        }
    }
    /**
     * @phpstan-impure
     */
    public function scan(string $string): bool
    {
        if (!parent::scan($string)) {
            return false;
        }
        $newlines = $this->newlines_in($string);
        $this->line += \count($newlines);
        if ($newlines === []) {
            $this->column += \strlen($string);
        } else {
            $last = $newlines[\count($newlines) - 1];
            $end = $last[1] + \strlen($last[0]);
            $this->column = \strlen($string) - $end;
        }
        return true;
    }
    /**
     * @return list<array{string, int}>
     */
    private function newlines_in(string $text): array
    {
        preg_match_all('/\r\n?|\n/', $text, $matches, PREG_OFFSET_CAPTURE);
        $newlines = $matches[0];
        if ($this->between_crlf()) {
            array_pop($newlines);
        }
        return $newlines;
    }
}