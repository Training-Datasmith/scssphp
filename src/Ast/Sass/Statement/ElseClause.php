<?php

declare(strict_types=1);

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Ast\Sass\Statement;

/**
 * An `@else` clause in an `@if` rule.
 *
 * @internal
 */
final class ElseClause extends IfRuleClause implements \Stringable
{
    public function __toString(): string
    {
        return '@else {' . implode(' ', $this->getChildren()) . '}';
    }
}
