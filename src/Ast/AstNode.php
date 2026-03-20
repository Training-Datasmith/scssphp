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
namespace Scss_Php\Scss_Php\Ast;

use Source_Span\File_Span;
/**
 * A node in an abstract syntax tree.
 *
 * @internal
 */
interface Ast_Node extends \Stringable
{
    public function get_span(): File_Span;
}