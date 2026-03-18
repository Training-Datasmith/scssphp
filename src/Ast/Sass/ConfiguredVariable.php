<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Ast\Sass;

use ScssPhp\ScssPhp\Util\SpanUtil;
use SourceSpan\FileSpan;

/**
 * A variable configured by a `with` clause in a `@use` or `@forward` rule.
 *
 * @internal
 */
final class ConfiguredVariable implements SassNode, SassDeclaration
{
    private readonly FileSpan $span;

    public function __construct(private readonly string $name, private readonly Expression $expression, FileSpan $span, private readonly bool $guarded = false)
    {
        $this->span = $span;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function isGuarded(): bool
    {
        return $this->guarded;
    }

    public function getNameSpan(): FileSpan
    {
        return SpanUtil::initialIdentifier($this->span, 1);
    }

    public function __toString(): string
    {
        return '$' . $this->name . ': ' . $this->expression . ($this->guarded ? ' !default' : '');
    }
}
