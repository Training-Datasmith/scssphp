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

use Scss_Php\Scss_Php\Util\Path;
/**
 * @internal
 */
final class Import_Util
{
    /**
     * Resolves an imported path using the same logic as the filesystem importer.
     *
     * This tries to fill in extensions and partial prefixes and check for a
     * directory default. If no file can be found, it returns `null`.
     */
    public static function resolve_import_path(string $path): ?string
    {
        $extension = Path::extension($path);
        if ($extension === '.sass' || $extension === '.scss' || $extension === '.css') {
            return self::if_in_import(fn(): ?string => self::exactly_one(self::try_path(Path::without_extension($path) . '.import' . $extension))) ?? self::exactly_one(self::try_path($path));
        }
        return self::if_in_import(fn(): ?string => self::exactly_one(self::try_path_with_extensions($path . '.import'))) ?? self::exactly_one(self::try_path_with_extensions($path)) ?? self::try_path_as_directory($path);
    }
    /**
     * Like {@see tryPath}, but checks `.sass`, `.scss`, and `.css` extensions.
     *
     * @return list<string>
     */
    private static function try_path_with_extensions(string $path): array
    {
        $result = array_merge(self::try_path($path . '.sass'), self::try_path($path . '.scss'));
        if ($result !== []) {
            return $result;
        }
        return self::try_path($path . '.css');
    }
    /**
     * Returns the $path and/or the partial with the same name, if either or both
     * exists.
     *
     * If neither exists, returns an empty list.
     *
     * @return list<string>
     */
    private static function try_path(string $path): array
    {
        $partial = Path::join(dirname($path), '_' . basename($path));
        $candidates = [];
        if (is_file($partial)) {
            $candidates[] = $partial;
        }
        if (is_file($path)) {
            $candidates[] = $path;
        }
        return $candidates;
    }
    /**
     * Returns the resolved index file for $path if $path is a directory and the
     * index file exists.
     *
     * Otherwise, returns `null`.
     */
    private static function try_path_as_directory(string $path): ?string
    {
        if (!is_dir($path)) {
            return null;
        }
        return self::if_in_import(fn(): ?string => self::exactly_one(self::try_path_with_extensions(Path::join($path, 'index.import')))) ?? self::exactly_one(self::try_path_with_extensions(Path::join($path, 'index')));
    }
    /**
     * @param list<string> $paths
     */
    private static function exactly_one(array $paths): ?string
    {
        if (\count($paths) === 0) {
            return null;
        }
        if (\count($paths) === 1) {
            return $paths[0];
        }
        $formatted_pretty_paths = [];
        foreach ($paths as $path) {
            $formatted_pretty_paths[] = '  ' . Path::pretty_uri($path);
        }
        throw new \Exception("It's not clear which file to import. Found:\n" . implode("\n", $formatted_pretty_paths));
    }
    /**
     * If {@see ImportContext::isFromImport} is `true`, invokes callback and returns the result.
     *
     * Otherwise, returns `null`.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @return T|null
     */
    private static function if_in_import(callable $callback)
    {
        if (Import_Context::is_from_import()) {
            return $callback();
        }
        return null;
    }
}