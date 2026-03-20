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
namespace Scss_Php\Scss_Php\Ast\Sass\Expression;

use Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Callable_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Source_Span\File_Span;
/**
 * A ternary expression.
 *
 * This is defined as a separate syntactic construct rather than a normal
 * function because only one of the `$if-true` and `$if-false` arguments are
 * evaluated.
 *
 * @internal
 */
final class If_Expression implements Expression, Callable_Invocation
{
    private readonly File_Span $span;
    private static ?Argument_Declaration $declaration = null;
    public function __construct(
        /**
         * The arguments passed to `if()`.
         */
        private readonly Argument_Invocation $arguments,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    /**
     * The declaration of `if()`, as though it were a normal function.
     */
    public static function get_declaration(): Argument_Declaration
    {
        if (self::$declaration === null) {
            self::$declaration = Argument_Declaration::parse('@function if($condition, $if-true, $if-false) {');
        }
        return self::$declaration;
    }
    public function get_arguments(): Argument_Invocation
    {
        return $this->arguments;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Expression_Visitor $visitor)
    {
        return $visitor->visit_if_expression($this);
    }
    public function __toString(): string
    {
        return 'if' . $this->arguments;
    }
}