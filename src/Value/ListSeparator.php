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
 * An enum of list separator types.
 */
enum List_Separator
{
    case COMMA;
    case SPACE;
    case SLASH;
    case UNDECIDED;
    public function get_separator(): ?string
    {
        return match ($this) {
            self::COMMA => ',',
            self::SPACE => ' ',
            self::SLASH => '/',
            self::UNDECIDED => null,
        };
    }
}