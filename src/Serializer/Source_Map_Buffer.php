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
 * @internal
 */
interface Source_Map_Buffer extends String_Buffer
{
    /**
     * Runs $callback and associates all text written within it with $span.
     *
     * Specifically, this associates the point at the beginning of the written
     * text with {@see FileSpan::getStart()} and the point at the end of the
     * written text with {@see FileSpan::getEnd()}.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function for_span(File_Span $span, callable $callback);
    /**
     * Returns the source map for the file being written.
     *
     * If $prefix is passed, all the entries in the source map will be moved
     * forward by the number of characters and lines in $prefix.
     */
    public function build_source_map(?string $prefix): Single_Mapping;
}