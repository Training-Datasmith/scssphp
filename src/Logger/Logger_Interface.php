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
namespace Scss_Php\Scss_Php\Logger;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Source_Span\File_Span;
use Source_Span\Source_Span;
/**
 * Interface implemented by loggers for warnings and debug messages.
 *
 * The official Sass implementation recommends that loggers report the
 * messages immediately rather than waiting for the end of the
 * compilation, to provide a better debugging experience when the
 * compilation does not end (error or infinite loop after the warning
 * for instance).
 */
interface Logger_Interface
{
    /**
     * Emits a warning with the given message.
     *
     * If $span is passed, it's the location in the Sass source that generated
     * the warning. If $trace is passed, it's the Sass stack trace when the
     * warning was issued.
     * If $deprecation is non-null, it indicates that this is a deprecation
     * warning. Implementations should surface all this information to
     * the end user.
     */
    public function warn(string $message, ?Deprecation $deprecation = null, ?File_Span $span = null, ?Trace $trace = null): void;
    /**
     * Emits a debugging message associated with the given span.
     */
    public function debug(string $message, Source_Span $span): void;
}