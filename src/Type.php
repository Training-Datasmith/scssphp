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
namespace Scss_Php\Scss_Php;

/**
 * Block/node types
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
final class Type
{
    public const T_COLOR = 'color';
    /**
     * @internal
     */
    public const T_KEYWORD = 'keyword';
    public const T_LIST = 'list';
    public const T_MAP = 'map';
    public const T_NULL = 'null';
    public const T_NUMBER = 'number';
    public const T_STRING = 'string';
}