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
namespace Scss_Php\Scss_Php\Evaluation;

use Scss_Php\Scss_Php\Deprecation;
use Source_Span\File_Span;
/**
 * @internal
 */
abstract class Evaluation_Context
{
    private static ?Evaluation_Context $evaluation_context = null;
    /**
     * The current evaluation context.
     *
     * @throws \LogicException if there isn't a Sass stylesheet currently being
     * evaluated.
     */
    public static function get_current(): Evaluation_Context
    {
        if (self::$evaluation_context !== null) {
            return self::$evaluation_context;
        }
        throw new \LogicException('No Sass stylesheet is currently being evaluated.');
    }
    /**
     * Runs $callback with $context as {@see EvaluationContext::getCurrent()}.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    public static function with_evaluation_context(Evaluation_Context $context, callable $callback)
    {
        $old_context = self::$evaluation_context;
        self::$evaluation_context = $context;
        try {
            return $callback();
        } finally {
            self::$evaluation_context = $old_context;
        }
    }
    /**
     * Returns the span for the currently executing callable.
     *
     * For normal exception reporting, this should be avoided in favor of
     * throwing {@see SassScriptException}s. It should only be used when calling APIs
     * that require spans.
     *
     * @throws \LogicException if there isn't a callable being invoked.
     */
    abstract public function get_current_callable_span(): File_Span;
    /**
     * Prints a warning message associated with the current `@import` or function
     * call.
     *
     * If $deprecation is non-null`, the warning is emitted as a deprecation
     * warning of that type.
     */
    abstract public function warn(string $message, ?Deprecation $deprecation = null): void;
}