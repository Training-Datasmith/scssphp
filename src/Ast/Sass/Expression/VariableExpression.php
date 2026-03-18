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

namespace ScssPhp\ScssPhp\Ast\Sass\Expression;

use ScssPhp\ScssPhp\Ast\Sass\Expression;
use ScssPhp\ScssPhp\Ast\Sass\SassReference;
use ScssPhp\ScssPhp\Util\SpanUtil;
use ScssPhp\ScssPhp\Visitor\ExpressionVisitor;
use SourceSpan\FileSpan;

/**
 * A Sass variable.
 *
 * @internal
 */
final class VariableExpression implements Expression, SassReference
{
    private readonly FileSpan $span;

    public function __construct(/**
     * The name of this variable, with underscores converted to hyphens.
     */
    private readonly string $name, FileSpan $span, /**
     * The namespace of the variable being referenced, or `null` if it's
     * referenced without a namespace.
     */
    private readonly ?string $namespace = null)
    {
        $this->span = $span;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function getNameSpan(): FileSpan
    {
        if ($this->namespace === null) {
            return $this->span;
        }

        return SpanUtil::withoutNamespace($this->span);
    }

    public function getNamespaceSpan(): ?FileSpan
    {
        if ($this->namespace === null) {
            return null;
        }

        return SpanUtil::initialIdentifier($this->span);
    }

    public function accept(ExpressionVisitor $visitor)
    {
        return $visitor->visitVariableExpression($this);
    }

    public function __toString(): string
    {
        return (string) $this->span->getText();
    }
}
