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
 * A target segment entry read from a source map
 *
 * @internal
 */
final class Target_Entry
{
    public function __construct(public readonly int $column, public readonly ?int $source_url_id = null, public readonly ?int $source_line = null, public readonly ?int $source_column = null)
    {
    }
}