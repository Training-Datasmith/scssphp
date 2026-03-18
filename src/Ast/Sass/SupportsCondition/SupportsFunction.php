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

namespace ScssPhp\ScssPhp\Ast\Sass\SupportsCondition;

use ScssPhp\ScssPhp\Ast\Sass\Interpolation;
use ScssPhp\ScssPhp\Ast\Sass\SupportsCondition;
use SourceSpan\FileSpan;

/**
 * A function-syntax condition.
 *
 * @internal
 */
final class SupportsFunction implements SupportsCondition
{
    private readonly FileSpan $span;

    public function __construct(/**
     * The name of the function.
     */
        private readonly Interpolation $name, /**
     * The arguments of the function.
     */
        private readonly Interpolation $arguments,
        FileSpan $span
    ) {
        $this->span = $span;
    }

    public function getName(): Interpolation
    {
        return $this->name;
    }

    public function getArguments(): Interpolation
    {
        return $this->arguments;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function __toString(): string
    {
        return "$this->name($this->arguments)";
    }
}
