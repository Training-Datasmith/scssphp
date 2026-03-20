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

use Scss_Php\Scss_Php\Serializer\Serializer;
use Scss_Php\Scss_Php\Util\Equatable;
/**
 * A binary operation that can appear in a {@see SassCalculation}.
 */
final class Calculation_Operation implements Equatable, \Stringable
{
    public function __construct(
        private readonly Calculation_Operator $operator,
        /**
         * The left-hand operand.
         *
         * This is either a {@see SassNumber}, a {@see SassCalculation}, an unquoted
         * {@see SassString}, or a {@see CalculationOperation}.
         */
        private readonly object $left,
        /**
         * The right-hand operand.
         *
         * This is either a {@see SassNumber}, a {@see SassCalculation}, an unquoted
         * {@see SassString}, or a {@see CalculationOperation}.
         */
        private readonly object $right
    )
    {
    }
    public function get_operator(): Calculation_Operator
    {
        return $this->operator;
    }
    public function get_left(): object
    {
        return $this->left;
    }
    public function get_right(): object
    {
        return $this->right;
    }
    public function equals(object $other): bool
    {
        assert($this->left instanceof Equatable);
        assert($this->right instanceof Equatable);
        return $other instanceof Calculation_Operation && $this->operator === $other->operator && $this->left->equals($other->left) && $this->right->equals($other->right);
    }
    public function __toString(): string
    {
        $parenthesized = Serializer::serialize_value(Sass_Calculation::unsimplified('', [$this]), true);
        return substr($parenthesized, 1, \strlen($parenthesized) - 2);
    }
}