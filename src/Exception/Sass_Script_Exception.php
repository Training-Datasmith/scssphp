<?php

declare (strict_types=1);
namespace Scss_Php\Scss_Php\Exception;

use Jiri_Pudil\Sealed_Classes\Sealed;
use Source_Span\File_Span;
/**
 * An exception thrown by SassScript.
 *
 * This class does not implement SassException on purpose, as it should
 * never be returned to the outside code. The compilation will catch it
 * and replace it with a SassException reporting the location of the
 * error.
 */
#[Sealed([Multi_Span_Sass_Script_Exception::class])]
class Sass_Script_Exception extends \Exception
{
    /**
     * Creates a SassScriptException with support for an argument name.
     *
     * This helper ensures a consistent handling of argument names in the
     * error message, without duplicating it.
     *
     * @param string|null $name The argument name, without $
     */
    public static function for_argument(string $message, ?string $name = null, ?\Throwable $previous = null): Sass_Script_Exception
    {
        $var_display = !\is_null($name) ? "\${$name}: " : '';
        return new self($var_display . $message, 0, $previous);
    }
    /**
     * Converts this to a {@see SassException} with the given $span.
     *
     * @internal
     */
    public function with_span(File_Span $span): Sass_Exception
    {
        return new Simple_Sass_Exception($this->message, $span, $this);
    }
}