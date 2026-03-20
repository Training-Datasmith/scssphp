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
use Source_Span\File_Span;
/**
 * An abstract class for callables (functions or mixins) that are declared in
 * user code.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
abstract class Callable_Declaration extends Parent_Statement
{
    private readonly string $name;
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly string $original_name, private readonly Argument_Declaration $arguments, File_Span $span, array $children, private readonly ?Silent_Comment $comment = null)
    {
        $this->name = str_replace('_', '-', $this->original_name);
        $this->span = $span;
        parent::__construct($children);
    }
    /**
     * The name of this callable, with underscores converted to hyphens.
     */
    final public function get_name(): string
    {
        return $this->name;
    }
    /**
     * The callable's original name, without underscores converted to hyphens.
     */
    public function get_original_name(): string
    {
        return $this->original_name;
    }
    final public function get_arguments(): Argument_Declaration
    {
        return $this->arguments;
    }
    final public function get_comment(): ?Silent_Comment
    {
        return $this->comment;
    }
    final public function get_span(): File_Span
    {
        return $this->span;
    }
}