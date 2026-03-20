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

/**
 * @internal
 */
enum Media_Query_Singleton_Merge_Result implements Media_Query_Merge_Result
{
    case empty;
    case unrepresentable;
}