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
namespace Scss_Php\Scss_Php\Source_Map\Builder;

use Source_Span\Source_Location;
/**
 * An entry in the source map builder.
 *
 * @internal
 */
final class Entry
{
    /**
     * Span denoting the original location in the input source file
     */
    public readonly Source_Location $source;
    /**
     * Span indicating the corresponding location in the target file.
     */
    public readonly Source_Location $target;
    public function __construct(Source_Location $source, Source_Location $target)
    {
        $this->source = $source;
        $this->target = $target;
    }
    /**
     * Implements comparison to ensure that entries are ordered by their
     * location in the target file. We sort primarily by the target offset
     * because source map files are encoded by printing each mapping in order as
     * they appear in the target file.
     */
    public function compare_to(Entry $other): int
    {
        $res = $this->target->compare_to($other->target);
        if ($res !== 0) {
            return $res;
        }
        $res = (string) $this->source->get_source_url() <=> (string) $other->source->get_source_url();
        if ($res !== 0) {
            return $res;
        }
        return $this->source->compare_to($other->source);
    }
}