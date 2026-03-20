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

use League\Uri\Contracts\Uri_Interface;
/**
 * @internal
 */
final class Canonicalize_Context
{
    private bool $containing_url_accessed = false;
    public function __construct(private readonly ?Uri_Interface $containing_url, private bool $from_import)
    {
    }
    /**
     * Whether the Sass compiler is currently evaluating an `@import` rule.
     */
    public function is_from_import(): bool
    {
        return $this->from_import;
    }
    /**
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    public function with_from_import(bool $from_import, callable $callback)
    {
        $old_from_import = $this->from_import;
        $this->from_import = $from_import;
        try {
            return $callback();
        } finally {
            $this->from_import = $old_from_import;
        }
    }
    public function get_containing_url(): ?Uri_Interface
    {
        $this->containing_url_accessed = true;
        return $this->containing_url;
    }
    /**
     * Whether {@see getContainingUrl} has been accessed.
     *
     * This is used to determine whether canonicalize result is cacheable.
     */
    public function was_containing_url_accessed(): bool
    {
        return $this->containing_url_accessed;
    }
}