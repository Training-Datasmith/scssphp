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
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Logger\Quiet_Logger;
use Scss_Php\Scss_Php\Util\Uri_Util;
/**
 * An in-memory cache of parsed stylesheets that have been imported by Sass.
 *
 * @internal
 */
final class Import_Cache
{
    /**
     * The canonicalized URLs for each non-canonical URL.
     *
     * The `forImport` in each key is true when this canonicalization is for an
     * `@import` rule. Otherwise, it's for a `@use` or `@forward` rule.
     *
     * This cache covers loads that go through the entire chain of {@see $importers},
     * but it doesn't cover individual loads or loads in which any importer
     * accesses `containingUrl`. See also {@see $perImporterCanonicalizeCache}.
     *
     * @var array<string, array<0|1, CanonicalizeResult|SpecialCacheValue>>
     */
    private array $canonicalize_cache = [];
    /**
     * Like {@see $canonicalizeCache} but also includes the specific importer in the
     * key.
     *
     * This is used to cache both relative imports from the base importer and
     * individual importer results in the case where some other component of the
     * importer chain isn't cacheable.
     *
     * @var \SplObjectStorage<Importer, array<string, array<0|1, CanonicalizeResult|SpecialCacheValue>>>
     */
    private \Spl_Object_Storage $per_importer_canonicalize_cache;
    /**
     * The parsed stylesheets for each canonicalized import URL.
     *
     * @var array<string, Stylesheet|SpecialCacheValue>
     */
    private array $import_cache = [];
    /**
     * The import results for each canonicalized import URL.
     *
     * @var array<string, ImporterResult>
     */
    private array $results_cache = [];
    /**
     * @param list<Importer> $importers
     */
    public function __construct(private readonly array $importers, private readonly Logger_Interface $logger)
    {
        $this->per_importer_canonicalize_cache = new \Spl_Object_Storage();
    }
    public function canonicalize(Uri_Interface $url, ?Importer $base_importer = null, ?Uri_Interface $base_url = null, bool $for_import = false): ?Canonicalize_Result
    {
        $url_cache_key = (string) $url;
        $for_import_cache_key = (int) $for_import;
        if ($base_importer !== null && $url->get_scheme() === null) {
            $resolved_url = self::resolve_uri($base_url, $url);
            $resolved_url_cache_key = (string) $resolved_url;
            if (!isset($this->per_importer_canonicalize_cache[$base_importer][$resolved_url_cache_key][$for_import_cache_key])) {
                [$result, $cacheable] = $this->do_canonicalize($base_importer, $resolved_url, $base_url, $for_import);
                \assert($cacheable, 'Relative loads should always be cacheable because they never provide access to the containing URL.');
                $importer_cache = $this->per_importer_canonicalize_cache[$base_importer] ?? [];
                $importer_cache[$resolved_url_cache_key][$for_import_cache_key] = $result ?? Special_Cache_Value::null;
                $this->per_importer_canonicalize_cache[$base_importer] = $importer_cache;
            }
            $relative_result = $this->per_importer_canonicalize_cache[$base_importer][$resolved_url_cache_key][$for_import_cache_key];
            if ($relative_result !== Special_Cache_Value::null) {
                return $relative_result;
            }
        }
        if (isset($this->canonicalize_cache[$url_cache_key][$for_import_cache_key])) {
            $cache_result = $this->canonicalize_cache[$url_cache_key][$for_import_cache_key];
            if ($cache_result !== Special_Cache_Value::null) {
                return $cache_result;
            }
            return null;
        }
        // Each individual call to a `canonicalize()` override may not be cacheable
        // (specifically, if it has access to `containingUrl` it's too
        // context-sensitive to usefully cache). We want to cache a given URL across
        // the _entire_ importer chain, so we use $cacheable to track whether _all_
        // `canonicalize()` calls we've attempted are cacheable. Only if they are, do
        // we store the result in the cache.
        $cacheable = true;
        foreach ($this->importers as $i => $importer) {
            if (isset($this->per_importer_canonicalize_cache[$importer][$url_cache_key][$for_import_cache_key])) {
                $result = $this->per_importer_canonicalize_cache[$importer][$url_cache_key][$for_import_cache_key];
                if ($result !== Special_Cache_Value::null) {
                    return $result;
                }
                continue;
            }
            [$result, $importer_cacheable] = $this->do_canonicalize($importer, $url, $base_url, $for_import);
            if ($result !== null && $importer_cacheable && $cacheable) {
                $this->canonicalize_cache[$url_cache_key][$for_import_cache_key] = $result;
                return $result;
            }
            if ($importer_cacheable && !$cacheable) {
                $importer_cache = $this->per_importer_canonicalize_cache[$importer] ?? [];
                $importer_cache[$url_cache_key][$for_import_cache_key] = $result ?? Special_Cache_Value::null;
                $this->per_importer_canonicalize_cache[$importer] = $importer_cache;
                if ($result !== null) {
                    return $result;
                }
            }
            if (!$importer_cacheable) {
                if ($cacheable) {
                    // If this is the first uncacheable result, add all previous results
                    // to the per-importer cache so we don't have to re-run them for
                    // future uses of this importer.
                    for ($j = 0; $j < $i; ++$j) {
                        $importer_cache = $this->per_importer_canonicalize_cache[$this->importers[$j]] ?? [];
                        $importer_cache[$url_cache_key][$for_import_cache_key] = Special_Cache_Value::null;
                        $this->per_importer_canonicalize_cache[$this->importers[$j]] = $importer_cache;
                    }
                    $cacheable = false;
                }
                if ($result !== null) {
                    return $result;
                }
            }
        }
        if ($cacheable) {
            $this->canonicalize_cache[$url_cache_key][$for_import_cache_key] = Special_Cache_Value::null;
        }
        return null;
    }
    private static function resolve_uri(?Uri_Interface $base_url, Uri_Interface $url): Uri_Interface
    {
        if ($base_url === null) {
            return $url;
        }
        return Uri_Util::resolve_uri($base_url, $url);
    }
    /**
     * Calls {@see Importer::canonicalize} and prints a deprecation warning if it
     * returns a relative URL.
     *
     * This returns both the result of the call to `canonicalize()` and whether
     * that result is cacheable at all.
     *
     * @return array{CanonicalizeResult|null, bool}
     */
    private function do_canonicalize(Importer $importer, Uri_Interface $url, ?Uri_Interface $base_url, bool $for_import): array
    {
        $pass_containing_url = $base_url !== null && ($url->get_scheme() === null || $importer->is_non_canonical_scheme($url->get_scheme()));
        $canonicalize_context = new Canonicalize_Context($pass_containing_url ? $base_url : null, $for_import);
        $result = Import_Context::with_canonicalize_context($canonicalize_context, fn(): ?\League\Uri\Contracts\Uri_Interface => $importer->canonicalize($url));
        $cacheable = !$pass_containing_url || !$canonicalize_context->was_containing_url_accessed();
        if ($result === null) {
            return [null, $cacheable];
        }
        if ($result->get_scheme() === null) {
            // dart-sass triggers a deprecation here. As we never supported the old behavior, we forbid it directly.
            throw new \UnexpectedValueException("Importer {$importer} canonicalized {$url} to {$result} but canonical URLs must be absolute.");
        }
        if ($importer->is_non_canonical_scheme($result->get_scheme())) {
            throw new \UnexpectedValueException("Importer {$importer} canonicalized {$url} to {$result}, which uses a scheme declared as non-canonical.");
        }
        return [new Canonicalize_Result($importer, $result, $url), $cacheable];
    }
    /**
     * Tries to load the canonicalized $canonicalUrl using $importer.
     *
     * If $importer can import $canonicalUrl, returns the imported {@see Stylesheet}.
     * Otherwise returns `null`.
     *
     * If passed, the $originalUrl represents the URL that was canonicalized
     * into $canonicalUrl. It's used to resolve a relative canonical URL, which
     * importers may return for legacy reasons.
     *
     * If $quiet is `true`, this will disable logging warnings when parsing the
     * newly imported stylesheet.
     *
     * Caches the result of the import and uses cached results if possible.
     */
    public function import_canonical(Importer $importer, Uri_Interface $canonical_url, ?Uri_Interface $original_url = null, bool $quiet = false): ?Stylesheet
    {
        $result = $this->import_cache[(string) $canonical_url] ??= $this->do_import_canonical($importer, $canonical_url, $original_url, $quiet) ?? Special_Cache_Value::null;
        if ($result !== Special_Cache_Value::null) {
            return $result;
        }
        return null;
    }
    private function do_import_canonical(Importer $importer, Uri_Interface $canonical_url, ?Uri_Interface $original_url = null, bool $quiet = false): ?Stylesheet
    {
        $result = $importer->load($canonical_url);
        if ($result === null) {
            return null;
        }
        $this->results_cache[(string) $canonical_url] = $result;
        return Stylesheet::parse($result->get_contents(), $result->get_syntax(), $quiet ? new Quiet_Logger() : $this->logger, self::resolve_uri($original_url, $canonical_url));
    }
    public function humanize(Uri_Interface $canonical_url): Uri_Interface
    {
        $shortest_url = null;
        $shortest_length = \PHP_INT_MAX;
        foreach ($this->canonicalize_cache as $cache_values) {
            foreach ($cache_values as $cache_value) {
                if ($cache_value === Special_Cache_Value::null) {
                    continue;
                }
                if ($cache_value->canonical_url->to_string() !== $canonical_url->to_string()) {
                    continue;
                }
                $original_url_length = \strlen((string) $cache_value->original_url->get_path());
                if ($shortest_url === null || $original_url_length < $shortest_length) {
                    $shortest_url = $cache_value->original_url;
                    $shortest_length = $original_url_length;
                }
            }
        }
        if ($shortest_url !== null) {
            return Uri_Util::resolve($shortest_url, basename($canonical_url->get_path()));
        }
        return $canonical_url;
    }
    public function source_map_url(Uri_Interface $canonical_url): Uri_Interface
    {
        return ($this->results_cache[(string) $canonical_url] ?? null)?->get_source_map_url() ?? $canonical_url;
    }
}