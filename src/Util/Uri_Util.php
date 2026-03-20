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

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use League\Uri\Uri_String;
/**
 * @internal
 */
final class Uri_Util
{
    public static function resolve(Uri_Interface $base_url, string $reference): Uri_Interface
    {
        return self::resolve_uri($base_url, Uri::new($reference));
    }
    public static function resolve_uri(Uri_Interface $base_url, Uri_Interface $url): Uri_Interface
    {
        if ($base_url->get_scheme() !== null) {
            // non-RFC3986 behavior in Dart-Sass when resolving relative reference with a base url with no authority and a relative path (where they consider the base path as absolute)
            if ($base_url->get_authority() === null && $base_url->get_path() !== '' && $base_url->get_path()[0] !== '/' && $url->get_scheme() === null && $url->get_authority() === null && $url->get_path() !== '' && $url->get_path()[0] !== '/') {
                return self::resolve_league_uri($base_url->with_path('/' . $base_url->get_path()), $url);
            }
            return self::resolve_league_uri($base_url, $url);
        }
        if ($url->get_scheme() !== null) {
            return $url->with_path(Uri_String::remove_dot_segments($url->get_path()));
        }
        if ($base_url->get_authority() !== null || $url->get_authority() !== null) {
            return self::resolve_league_uri($base_url->with_scheme('scssphp-resolve'), $url)->with_scheme(null);
        }
        if ($url->get_path() === '') {
            if ($url->get_query() !== null) {
                return $base_url->with_query($url->get_query())->with_fragment($url->get_fragment());
            }
            return $base_url->with_fragment($url->get_fragment());
        }
        if ($url->get_path()[0] === '/') {
            $new_path = Uri_String::remove_dot_segments($url->get_path());
            if ($new_path !== '' && $new_path[0] !== '/') {
                $new_path = '/' . $new_path;
            }
            return $url->with_path($new_path);
        }
        if ($base_url->get_path() === '') {
            return $url;
        }
        if ($base_url->get_path()[0] !== '/') {
            // Pure path resolution between 2 relative path URLs
            $merged_path = self::normalize_relative_path(self::merge_paths($base_url->get_path(), $url->get_path()));
            return $url->with_path($merged_path);
        }
        return self::resolve_league_uri($base_url->with_scheme('scssphp-resolve')->with_host('localhost'), $url)->with_scheme(null)->with_host(null);
    }
    private static function resolve_league_uri(Uri_Interface $base_url, Uri_Interface $url): Uri_Interface
    {
        // Custom implementations of UriInterface might not implement the resolve method yet, until version 8.0 of the interface.
        if (!$base_url instanceof Uri && !method_exists($base_url, 'resolve')) {
            $base_url = Uri::new($base_url);
        }
        return $base_url->resolve($url);
    }
    /**
     * @param non-empty-string $base
     * @param non-empty-string $reference
     *
     * @return non-empty-string
     */
    private static function merge_paths(string $base, string $reference): string
    {
        \assert($reference[0] !== '/');
        $base_end = strrpos($base, '/');
        if ($base_end === false) {
            return $reference;
        }
        return substr($base, 0, $base_end + 1) . $reference;
    }
    /**
     * Removes all `.` segments and any non-leading `..` segments.
     *
     * Removing the ".." from a "bar/foo/.." sequence results in "bar/"
     * (trailing "/"). If the entire path is removed (because it contains as
     * many ".." segments as real segments), the result is "./".
     * This is different from an empty string, which represents "no path"
     * when you resolve it against a base URI with a path with a non-empty
     * final segment.
     *
     * @param non-empty-string $path
     */
    private static function normalize_relative_path(string $path): string
    {
        \assert($path[0] !== '/');
        if ($path[0] !== '.' && !str_contains($path, '/.')) {
            return $path;
        }
        $output = [];
        $append_slash = false;
        foreach (explode('/', $path) as $segment) {
            $append_slash = false;
            if ('..' === $segment) {
                if ($output !== [] && List_Util::last($output) !== '..') {
                    array_pop($output);
                    $append_slash = true;
                } else {
                    $output[] = '..';
                }
            } elseif ('.' === $segment) {
                $append_slash = true;
            } else {
                $output[] = $segment;
            }
        }
        if ($output === [] || $output === ['']) {
            return './';
        }
        if ($append_slash || List_Util::last($output) === '..') {
            $output[] = '';
        }
        return implode('/', $output);
    }
}