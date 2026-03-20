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
use Scss_Php\Scss_Php\Util\Path;
/**
 * @internal
 */
final class Legacy_Callback_Importer extends Importer
{
    private readonly Importer $filesystem_importer;
    /**
     * @param \Closure(string): (string|null) $callback
     */
    public function __construct(private readonly \Closure $callback)
    {
        $this->filesystem_importer = new Filesystem_Importer(null);
    }
    public function canonicalize(Uri_Interface $url): ?Uri_Interface
    {
        if ($url->get_scheme() === 'file') {
            return $this->filesystem_importer->canonicalize($url);
        }
        $result = ($this->callback)((string) $url);
        if ($result === null) {
            return null;
        }
        $result_url = Path::to_uri($result);
        return $this->filesystem_importer->canonicalize($result_url);
    }
    public function load(Uri_Interface $url): ?Importer_Result
    {
        return $this->filesystem_importer->load($url);
    }
    public function could_canonicalize(Uri_Interface $url, Uri_Interface $canonical_url): bool
    {
        return $this->filesystem_importer->could_canonicalize($url, $canonical_url);
    }
    public function __toString(): string
    {
        return 'LegacyCallbackImporter';
    }
}