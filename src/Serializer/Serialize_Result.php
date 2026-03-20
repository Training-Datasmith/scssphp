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
namespace Scss_Php\Scss_Php\Serializer;

use Scss_Php\Scss_Php\Source_Map\Single_Mapping;
/**
 * The result of converting a CSS AST to CSS text.
 *
 * @internal
 */
final class Serialize_Result
{
    public function __construct(public readonly string $css, public readonly ?Single_Mapping $mapping)
    {
    }
}