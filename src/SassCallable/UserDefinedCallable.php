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
namespace Scss_Php\Scss_Php\Sass_Callable;

use Scss_Php\Scss_Php\Ast\Sass\Statement\Callable_Declaration;
use Scss_Php\Scss_Php\Evaluation\Environment;
/**
 * A callback defined in the user's Sass stylesheet.
 *
 * @internal
 */
final class User_Defined_Callable implements Sass_Callable
{
    public function __construct(private readonly Callable_Declaration $declaration, private readonly Environment $environment, private readonly bool $in_dependency)
    {
    }
    public function get_declaration(): Callable_Declaration
    {
        return $this->declaration;
    }
    public function get_environment(): Environment
    {
        return $this->environment;
    }
    public function is_in_dependency(): bool
    {
        return $this->in_dependency;
    }
    public function get_name(): string
    {
        return $this->declaration->get_name();
    }
}