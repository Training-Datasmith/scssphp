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
namespace Scss_Php\Scss_Php\Ast\Sass;

use Source_Span\File_Span;
/**
 * A common interface for any node that declares a Sass member.
 *
 * @internal
 */
interface Sass_Declaration extends Sass_Node
{
    /**
     * The name of the declaration, with underscores converted to hyphens.
     *
     * This does not include the `$` for variables.
     */
    public function get_name(): string;
    /**
     * The span containing this declaration's name.
     *
     * This includes the `$` for variables.
     */
    public function get_name_span(): File_Span;
}