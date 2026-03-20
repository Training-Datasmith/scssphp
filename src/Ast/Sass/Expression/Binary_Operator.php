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
enum Binary_Operator
{
    case SINGLE_EQUALS;
    case OR;
    case AND;
    case EQUALS;
    case NOT_EQUALS;
    case GREATER_THAN;
    case GREATER_THAN_OR_EQUALS;
    case LESS_THAN;
    case LESS_THAN_OR_EQUALS;
    case PLUS;
    case MINUS;
    case TIMES;
    case DIVIDED_BY;
    case MODULO;
    /**
     * The Sass syntax for this operator
     */
    public function get_operator(): string
    {
        return match ($this) {
            self::SINGLE_EQUALS => '=',
            self::OR => 'or',
            self::AND => 'and',
            self::EQUALS => '==',
            self::NOT_EQUALS => '!=',
            self::GREATER_THAN => '>',
            self::GREATER_THAN_OR_EQUALS => '>=',
            self::LESS_THAN => '<',
            self::LESS_THAN_OR_EQUALS => '<=',
            self::PLUS => '+',
            self::MINUS => '-',
            self::TIMES => '*',
            self::DIVIDED_BY => '/',
            self::MODULO => '%',
        };
    }
    public function get_precedence(): int
    {
        return match ($this) {
            self::SINGLE_EQUALS => 0,
            self::OR => 1,
            self::AND => 2,
            self::EQUALS, self::NOT_EQUALS => 3,
            self::GREATER_THAN, self::GREATER_THAN_OR_EQUALS, self::LESS_THAN, self::LESS_THAN_OR_EQUALS => 4,
            self::PLUS, self::MINUS => 5,
            self::TIMES, self::DIVIDED_BY, self::MODULO => 6,
        };
    }
    /**
     * Whether this operation has the [associative property].
     *
     * [associative property]: https://en.wikipedia.org/wiki/Associative_property
     */
    public function is_associative(): bool
    {
        return match ($this) {
            self::OR, self::AND, self::PLUS, self::TIMES => true,
            default => false,
        };
    }
}