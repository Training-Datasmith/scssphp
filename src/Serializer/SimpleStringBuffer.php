<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Serializer;

use Scss_Php\Scss_Php\Source_Map\Single_Mapping;
use Source_Span\File_Span;
/**
 * A buffer that doesn't actually build a source map.
 *
 * We implement {@see SourceMapBuffer} directly on SimpleStringBuffer to avoid
 * an unnecessary wrapper for NoSourceMapBuffer (dart-sass has to make a wrapper
 * because StringBuffer comes from dart core).
 *
 * @internal
 */
final class Simple_String_Buffer implements Source_Map_Buffer
{
    private string $text = '';
    public function get_length(): int
    {
        return \strlen($this->text);
    }
    public function write(string $string): void
    {
        $this->text .= $string;
    }
    public function write_char(string $char): void
    {
        $this->text .= $char;
    }
    public function __toString(): string
    {
        return $this->text;
    }
    public function for_span(File_Span $span, callable $callback)
    {
        return $callback();
    }
    public function build_source_map(?string $prefix): Single_Mapping
    {
        throw new \BadMethodCallException(__METHOD__ . ' is not supported.');
    }
}