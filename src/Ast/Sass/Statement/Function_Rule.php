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
namespace Scss_Php\Scss_Php\Ast\Sass\Statement;

use Scss_Php\Scss_Php\Ast\Sass\Sass_Declaration;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A function declaration.
 *
 * This declares a function that's invoked using normal CSS function syntax.
 *
 * @internal
 */
final class Function_Rule extends Callable_Declaration implements Sass_Declaration
{
    public function get_name_span(): File_Span
    {
        return Span_Util::initial_identifier(Span_Util::without_initial_at_rule($this->get_span()));
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_function_rule($this);
    }
    public function __toString(): string
    {
        return '@function ' . $this->get_name() . '(' . $this->get_arguments() . ') {' . implode(' ', $this->get_children()) . '}';
    }
}