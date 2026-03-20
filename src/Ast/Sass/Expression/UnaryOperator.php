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
namespace Scss_Php\Scss_Php\Ast\Sass\Expression;

/**
 * @internal
 */
enum Unary_Operator
{
    case PLUS;
    case MINUS;
    case DIVIDE;
    case NOT;
    /**
     * The Sass syntax for this operator
     */
    public function get_operator(): string
    {
        return match ($this) {
            self::PLUS => '+',
            self::MINUS => '-',
            self::DIVIDE => '/',
            self::NOT => 'not',
        };
    }
}