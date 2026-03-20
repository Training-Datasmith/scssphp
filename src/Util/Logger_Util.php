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
namespace Scss_Php\Scss_Php\Util;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Logger\Deprecation_Processing_Logger;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Logger_Util
{
    public static function warn_for_deprecation(Logger_Interface $logger, Deprecation $deprecation, string $message, ?File_Span $span = null, ?Trace $trace = null): void
    {
        if ($deprecation->is_future() && !$logger instanceof Deprecation_Processing_Logger) {
            return;
        }
        $logger->warn($message, $deprecation, $span, $trace);
    }
}