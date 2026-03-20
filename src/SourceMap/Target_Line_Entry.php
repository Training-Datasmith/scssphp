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
namespace Scss_Php\Scss_Php\Source_Map;

/**
 * @internal
 */
final class Target_Line_Entry
{
    /**
     * @param \ArrayObject<int, TargetEntry> $entries
     */
    public function __construct(public readonly int $line, public readonly \ArrayObject $entries)
    {
    }
}