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

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Source_Span\File_Span;
/**
 * A buffer that iteratively builds up an {@see Interpolation}.
 *
 * @internal
 */
final class Interpolation_Buffer
{
    private string $text = '';
    /**
     * @var list<string|Expression>
     */
    private array $contents = [];
    /**
     * Returns the substring of the buffer string after the last interpolation.
     */
    public function get_trailing_string(): string
    {
        return $this->text;
    }
    public function is_empty(): bool
    {
        return $this->text === '' && \count($this->contents) === 0;
    }
    public function write(string $string): void
    {
        $this->text .= $string;
    }
    public function add(Expression $expression): void
    {
        $this->flush_text();
        $this->contents[] = $expression;
    }
    public function add_interpolation(Interpolation $interpolation): void
    {
        $contents = $interpolation->get_contents();
        if (empty($contents)) {
            return;
        }
        if (is_string($contents[0])) {
            $this->text .= $contents[0];
            array_shift($contents);
        }
        $this->flush_text();
        foreach ($contents as $content) {
            $this->contents[] = $content;
        }
        if (\is_string($this->contents[\count($this->contents) - 1])) {
            $this->text = $this->contents[\count($this->contents) - 1];
            array_pop($this->contents);
        }
    }
    public function build_interpolation(File_Span $span): Interpolation
    {
        $contents = $this->contents;
        if ($this->text !== '') {
            $contents[] = $this->text;
        }
        return new Interpolation($contents, $span);
    }
    /**
     * Flushes {@see self::$text} to {@see self::$contents} if necessary.
     */
    private function flush_text(): void
    {
        if ($this->text === '') {
            return;
        }
        $this->contents[] = $this->text;
        $this->text = '';
    }
}