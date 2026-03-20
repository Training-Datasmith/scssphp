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
namespace Scss_Php\Scss_Php\Ast\Css;

/**
 * A plain CSS comment.
 *
 * This is always a multi-line comment.
 *
 * @internal
 */
interface Css_Comment extends Css_Node
{
    /**
     * The contents of this comment, including `/*` and `* /`.
     */
    public function get_text(): string;
    /**
     * Whether this comment starts with `/*!` and so should be preserved even in
     * compressed mode.
     */
    public function is_preserved(): bool;
}