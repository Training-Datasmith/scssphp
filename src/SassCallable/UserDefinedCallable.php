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

namespace ScssPhp\ScssPhp\SassCallable;

use ScssPhp\ScssPhp\Ast\Sass\Statement\CallableDeclaration;
use ScssPhp\ScssPhp\Evaluation\Environment;

/**
 * A callback defined in the user's Sass stylesheet.
 *
 * @internal
 */
final class UserDefinedCallable implements SassCallable
{
    public function __construct(private readonly CallableDeclaration $declaration, private readonly Environment $environment, private readonly bool $inDependency)
    {
    }

    public function getDeclaration(): CallableDeclaration
    {
        return $this->declaration;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function isInDependency(): bool
    {
        return $this->inDependency;
    }

    public function getName(): string
    {
        return $this->declaration->getName();
    }
}
