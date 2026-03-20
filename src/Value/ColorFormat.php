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
namespace Scss_Php\Scss_Php\Value;

use Jiri_Pudil\Sealed_Classes\Sealed;
/**
 * @internal
 */
#[Sealed(permits: [Color_Format_Enum::class, Span_Color_Format::class])]
interface Color_Format
{
}