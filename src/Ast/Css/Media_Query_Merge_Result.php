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
namespace Scss_Php\Scss_Php\Ast\Css;

use Jiri_Pudil\Sealed_Classes\Sealed;
/**
 * @internal
 */
#[Sealed(permits: [Css_Media_Query::class, Media_Query_Singleton_Merge_Result::class])]
interface Media_Query_Merge_Result
{
}