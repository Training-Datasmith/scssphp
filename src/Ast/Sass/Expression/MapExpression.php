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

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A map literal.
 *
 * @internal
 */
final class Map_Expression implements Expression
{
    private readonly File_Span $span;
    /**
     * @param list<array{Expression, Expression}> $pairs
     */
    public function __construct(private readonly array $pairs, File_Span $span)
    {
        $this->span = $span;
    }
    /**
     * @return list<array{Expression, Expression}>
     */
    public function get_pairs(): array
    {
        return $this->pairs;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_map_expression($this);
    }
    public function __toString(): string
    {
        return '(' . implode(', ', array_map(fn(array $pair): string => $pair[0] . ': ' . $pair[1], $this->pairs)) . ')';
    }
}