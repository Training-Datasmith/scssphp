<?php

declare (strict_types=1);
namespace Scss_Php\Scss_Php\Util;

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Path as SymfonyPath;
/**
 * @internal
 */
final class Path
{
    /**
     * @var array<string, string>
     */
    private static array $real_case_cache = [];
    public static function to_uri(string $path): Uri_Interface
    {
        if (\DIRECTORY_SEPARATOR === '\\') {
            return Uri::from_windows_path($path);
        }
        return Uri::from_unix_path($path);
    }
    public static function from_uri(Uri_Interface $uri): string
    {
        if (!$uri instanceof Uri) {
            $uri = Uri::new($uri);
        }
        if (\DIRECTORY_SEPARATOR === '\\') {
            return $uri->to_windows_path() ?? throw new \InvalidArgumentException("Uri {$uri} must have scheme 'file:'.");
        }
        return $uri->to_unix_path() ?? throw new \InvalidArgumentException("Uri {$uri} must have scheme 'file:'.");
    }
    public static function is_absolute(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        if ($path[0] === '/') {
            return true;
        }
        if (\DIRECTORY_SEPARATOR === '\\') {
            return self::is_windows_absolute($path);
        }
        return false;
    }
    /**
     * Canonicalizes $path.
     *
     * This is guaranteed to return the same path for two different input paths
     * if and only if both input paths point to the same location. Unlike
     * {@see normalize}, it returns absolute paths when possible and canonicalizes
     * ASCII case on Windows.
     *
     * Note that this does not resolve symlinks.
     */
    public static function canonicalize(string $path): string
    {
        return self::real_case_path(self::normalize(self::absolute($path)));
    }
    /**
     * Normalizes $path, simplifying it by handling `..`, and `.`, and
     * removing redundant path separators whenever possible.
     *
     * Note that this is *not* guaranteed to return the same result for two
     * equivalent input paths.
     */
    public static function normalize(string $path): string
    {
        $normalized = Symfony_Path::canonicalize($path);
        // The Symfony Path class always uses / as separator, while we want to use the platform one to get a real path
        if (\DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $normalized);
        }
        return $normalized;
    }
    /**
     * Attempts to convert $path to an equivalent relative path from $from.
     *
     * Since there is no relative path from one drive letter to another on Windows,
     * this will return an absolute path in those cases.
     */
    public static function relative(string $path, string $from): string
    {
        try {
            $relative_path = Symfony_Path::make_relative($path, $from);
        } catch (InvalidArgumentException) {
            return $path;
        }
        // The Symfony Path class always uses / as separator, while we want to use the platform one to get a real path
        if (\DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $relative_path);
        }
        return $relative_path;
    }
    private static function real_case_path(string $path): string
    {
        if (!(\PHP_OS_FAMILY === 'Windows' || \PHP_OS_FAMILY === 'Darwin')) {
            return $path;
        }
        if (\PHP_OS_FAMILY === 'Windows') {
            // Drive names are *always* case-insensitive, so convert them to uppercase.
            if (self::is_absolute($path) && Character::is_alphabetic($path[0])) {
                $path = strtoupper(substr($path, 0, 3)) . substr($path, 3);
            }
        }
        return self::real_case_path_helper($path);
    }
    private static function real_case_path_helper(string $path): string
    {
        $dirname = dirname($path);
        if ($dirname === $path || $dirname === '.') {
            return $path;
        }
        return self::$real_case_cache[$path] ??= self::compute_real_case_path($path);
    }
    private static function compute_real_case_path(string $path): string
    {
        $real_dirname = self::real_case_path_helper(dirname($path));
        $basename = basename($path);
        $files = @scandir($real_dirname);
        if ($files === false) {
            // If there's an error listing a directory, it's likely because we're
            // trying to reach too far out of the current directory into something
            // we don't have permissions for. In that case, just assume we have the
            // real path.
            return $path;
        }
        $matches = array_values(array_filter($files, fn(string $real_path): bool => String_Util::equals_ignore_case(basename($real_path), $basename)));
        if (\count($matches) === 1) {
            return self::join($real_dirname, $matches[0]);
        }
        // If the file doesn't exist, or if there are multiple options
        // (meaning the filesystem isn't actually case-insensitive), use
        // `basename` as-is.
        return self::join($real_dirname, $basename);
    }
    public static function is_windows_absolute(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        if ($path[0] === '/') {
            return true;
        }
        if ($path[0] === '\\') {
            return true;
        }
        if (\strlen($path) < 3) {
            return false;
        }
        if ($path[1] !== ':') {
            return false;
        }
        if ($path[2] !== '/' && $path[2] !== '\\') {
            return false;
        }
        if (!preg_match('/^[A-Za-z]$/', $path[0])) {
            return false;
        }
        return true;
    }
    public static function join(string $part1, string $part2): string
    {
        if ($part1 === '' || self::is_absolute($part2)) {
            return $part2;
        }
        if ($part2 === '') {
            return $part1;
        }
        $last = $part1[\strlen($part1) - 1];
        $separator = \DIRECTORY_SEPARATOR;
        if ($last === '/' || $last === \DIRECTORY_SEPARATOR) {
            $separator = '';
        }
        return $part1 . $separator . $part2;
    }
    public static function absolute(string $path): string
    {
        $cwd = getcwd();
        if ($cwd === false) {
            return $path;
        }
        return self::join($cwd, $path);
    }
    /**
     * Gets the file extension of $path: the portion of basename from the last
     * `.` to the end (including the `.` itself).
     *
     * If the file name starts with a `.`, then that is not considered the
     * extension
     */
    public static function extension(string $path): string
    {
        $basename = basename($path);
        $last_dot = strrpos($basename, '.');
        if ($last_dot === false || $last_dot === 0) {
            return '';
        }
        return substr($basename, $last_dot);
    }
    public static function without_extension(string $path): string
    {
        $extension = self::extension($path);
        if ($extension === '') {
            return $path;
        }
        return substr($path, 0, -\strlen($extension));
    }
    /**
     * Returns a pretty URI for a path
     */
    public static function pretty_uri(string|Uri_Interface $path): string
    {
        if ($path instanceof Uri_Interface) {
            if ($path->get_scheme() !== 'file') {
                return (string) $path;
            }
            $path = self::from_uri($path);
        }
        $normalized_path = $path;
        $normalized_root_directory = getcwd() . '/';
        if (\DIRECTORY_SEPARATOR === '\\') {
            $normalized_root_directory = str_replace('\\', '/', $normalized_root_directory);
            $normalized_path = str_replace('\\', '/', $path);
        }
        // TODO add support for returning a relative path using ../ in some cases, like Dart's path.prettyUri method
        if (str_starts_with($normalized_path, $normalized_root_directory)) {
            return substr($path, \strlen($normalized_root_directory));
        }
        return $path;
    }
}