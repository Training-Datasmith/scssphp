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
 * A logger that silently ignores all messages.
 */
final class Quiet_Logger implements Logger_Interface
{
    public function warn(string $message, ?Deprecation $deprecation = null, ?File_Span $span = null, ?Trace $trace = null): void
    {
    }
    public function debug(string $message, Source_Span $span): void
    {
    }
}