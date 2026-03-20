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
namespace Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;

use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Source_Span\File_Span;
/**
 * An operation defining the relationship between two conditions.
 *
 * @internal
 */
final class Supports_Operation implements Supports_Condition
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The left-hand operand.
         */
        private readonly Supports_Condition $left,
        /**
         * The right-hand operand.
         */
        private readonly Supports_Condition $right,
        private readonly string $operator,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_left(): Supports_Condition
    {
        return $this->left;
    }
    public function get_right(): Supports_Condition
    {
        return $this->right;
    }
    public function get_operator(): string
    {
        return $this->operator;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        return $this->parenthesize($this->left) . ' ' . $this->operator . ' ' . $this->parenthesize($this->right);
    }
    private function parenthesize(Supports_Condition $condition): string
    {
        if ($condition instanceof Supports_Negation || $condition instanceof Supports_Operation && $condition->operator === $this->operator) {
            return "({$condition})";
        }
        return (string) $condition;
    }
}