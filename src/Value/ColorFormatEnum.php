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

/**
 * @internal
 */
enum Color_Format_Enum implements Color_Format
{
    /**
     * A color defined using the `rgb()` or `rgba()` functions.
     */
    case rgbFunction;
    /**
     * A color defined using the `hsl()` or `hsla()` functions.
     */
    case hslFunction;
}