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
 * A negated condition.
 *
 * @internal
 */
final class Supports_Negation implements Supports_Condition
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The condition that's been negated.
         */
        private readonly Supports_Condition $condition,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_condition(): Supports_Condition
    {
        return $this->condition;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        if ($this->condition instanceof Supports_Negation || $this->condition instanceof Supports_Operation) {
            return "not ({$this->condition})";
        }
        return 'not ' . $this->condition;
    }
}