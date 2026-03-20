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
use Scss_Php\Scss_Php\Syntax;
use Scss_Php\Scss_Php\Util\Path;
/**
 * An importer that loads files from a load path on the filesystem.
 */
final class Filesystem_Importer extends Importer
{
    /**
     * The path relative to which this importer looks for files.
     *
     * If this is `null`, this importer will _only_ load absolute `file:` URLs
     * and URLs relative to the current file.
     */
    private readonly ?string $load_path;
    public function __construct(?string $load_path)
    {
        $this->load_path = $load_path !== null ? Path::absolute($load_path) : null;
    }
    public function canonicalize(Uri_Interface $url): ?Uri_Interface
    {
        if ($url->get_scheme() === 'file') {
            $resolved = Import_Util::resolve_import_path(Path::from_uri($url));
        } elseif ($url->get_scheme() !== null) {
            return null;
        } elseif ($this->load_path !== null) {
            $resolved = Import_Util::resolve_import_path(Path::join($this->load_path, Path::from_uri($url)));
        } else {
            return null;
        }
        if ($resolved === null) {
            return null;
        }
        return Path::to_uri(Path::canonicalize($resolved));
    }
    public function load(Uri_Interface $url): \Scss_Php\Scss_Php\Importer\Importer_Result
    {
        $path = Path::from_uri($url);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \Exception("Could not read file {$path}");
        }
        return new Importer_Result($content, Syntax::for_path($path), $url);
    }
    public function could_canonicalize(Uri_Interface $url, Uri_Interface $canonical_url): bool
    {
        if ($url->get_scheme() !== 'file' && $url->get_scheme() !== null) {
            return false;
        }
        if ($canonical_url->get_scheme() !== 'file') {
            return false;
        }
        $basename = basename((string) $url);
        $canonical_basename = basename((string) $canonical_url);
        if (!str_starts_with($basename, '_') && str_starts_with($canonical_basename, '_')) {
            $canonical_basename = substr($canonical_basename, 1);
        }
        return $basename === $canonical_basename || $basename === Path::without_extension($canonical_basename);
    }
    public function __toString(): string
    {
        return $this->load_path ?? '<absolute file importer>';
    }
}