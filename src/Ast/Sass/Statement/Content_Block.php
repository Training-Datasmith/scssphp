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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * An anonymous block of code that's invoked for a {@see ContentRule}.
 *
 * @internal
 */
final class Content_Block extends Callable_Declaration
{
    /**
     * @param Statement[] $children
     */
    public function __construct(Argument_Declaration $arguments, array $children, File_Span $span)
    {
        parent::__construct('@content', $arguments, $span, $children);
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_content_block($this);
    }
    public function __toString(): string
    {
        $buffer = $this->get_arguments()->is_empty() ? '' : ' using (' . $this->get_arguments() . ')';
        return $buffer . '{' . implode(' ', $this->get_children()) . '}';
    }
}