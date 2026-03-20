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

use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A `@content` rule.
 *
 * This is used in a mixin to include statement-level content passed by the
 * caller.
 *
 * @internal
 */
final class Content_Rule implements Statement
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The arguments pass to this `@content` rule.
         *
         * This will be an empty invocation if `@content` has no arguments.
         */
        private readonly Argument_Invocation $arguments,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_arguments(): Argument_Invocation
    {
        return $this->arguments;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_content_rule($this);
    }
    public function __toString(): string
    {
        return $this->arguments->is_empty() ? '@content;' : "@content({$this->arguments});";
    }
}