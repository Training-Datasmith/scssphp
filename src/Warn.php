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
namespace Scss_Php\Scss_Php;

use Scss_Php\Scss_Php\Evaluation\Evaluation_Context;
final class Warn
{
    /**
     * Prints a warning message associated with the current `@import` or function call.
     *
     * This may only be called within a custom function or importer callback.
     */
    public static function warning(string $message): void
    {
        self::report_warning($message, null);
    }
    /**
     * Prints a deprecation warning message associated with the current `@import` or function call.
     *
     * This may only be called within a custom function or importer callback.
     */
    public static function deprecation(string $message): void
    {
        self::report_warning($message, Deprecation::userAuthored);
    }
    public static function for_deprecation(string $message, Deprecation $deprecation): void
    {
        self::report_warning($message, $deprecation);
    }
    private static function report_warning(string $message, ?Deprecation $deprecation): void
    {
        Evaluation_Context::get_current()->warn($message, $deprecation);
    }
}