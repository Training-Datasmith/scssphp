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
namespace Scss_Php\Scss_Php\Importer;

/**
 * @internal
 */
final class Import_Context
{
    private static ?Canonicalize_Context $context = null;
    /**
     * Whether the Sass compiler is currently evaluating an `@import` rule.
     *
     * When evaluating `@import` rules, URLs should canonicalize to an import-only
     * file if one exists for the URL being canonicalized. Otherwise,
     * canonicalization should be identical for `@import` and `@use` rules. It's
     * admittedly hacky to set this globally, but `@import` will eventually be
     * removed, at which point we can delete this and have one consistent behavior.
     */
    public static function is_from_import(): bool
    {
        return self::$context?->is_from_import() ?? false;
    }
    /**
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    public static function in_import_rule(callable $callback)
    {
        if (self::$context !== null) {
            return self::$context->with_from_import(true, $callback);
        }
        return self::with_canonicalize_context(new Canonicalize_Context(null, true), $callback);
    }
    public static function get_canonicalize_context(): Canonicalize_Context
    {
        if (self::$context === null) {
            throw new \LogicException('canonicalizeContext may only be accessed within a call to canonicalize().');
        }
        return self::$context;
    }
    /**
     * Runs $callback in the given context.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    public static function with_canonicalize_context(?Canonicalize_Context $canonicalize_context, callable $callback)
    {
        $old_canonicalize_context = self::$context;
        self::$context = $canonicalize_context;
        try {
            return $callback();
        } finally {
            self::$context = $old_canonicalize_context;
        }
    }
}