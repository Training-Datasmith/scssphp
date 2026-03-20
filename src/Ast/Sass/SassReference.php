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
 * A common interface for any node that references a Sass member.
 *
 * @internal
 */
interface Sass_Reference extends Sass_Node
{
    /**
     * The namespace of the member being referenced, or `null` if it's referenced
     * without a namespace.
     */
    public function get_namespace(): ?string;
    /**
     * The name of the member being referenced, with underscores converted to
     * hyphens.
     *
     * This does not include the `$` for variables.
     */
    public function get_name(): string;
    /**
     * The span containing this reference's name.
     *
     * For variables, this should include the `$`.
     */
    public function get_name_span(): File_Span;
    /**
     * The span containing this reference's namespace, null if {@see getNamespace} is
     * null.
     */
    public function get_namespace_span(): ?File_Span;
}