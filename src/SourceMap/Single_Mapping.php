<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Source_Map;

use Scss_Php\Scss_Php\Source_Map\Builder\Entry;
use Source_Span\File_Location;
use Source_Span\Source_File;
/**
 * @internal
 */
final class Single_Mapping
{
    /**
     * Url of the target file.
     */
    public ?string $target_url = null;
    /**
     * Source root prepended to all entries in {@see $urls}.
     */
    public ?string $source_root = null;
    /**
     * @param list<SourceFile|null> $files
     * @param list<string> $urls
     * @param list<TargetLineEntry> $lines
     */
    private function __construct(
        /**
         * The {@see SourceFile}s to which the entries in {@see $lines} refer.
         *
         * This is in the same order as {@see $urls}. If this was constructed using
         * {@see SingleMapping::fromEntries()}, this contains files from any {@see FileLocation}s
         * used to build the mapping.
         *
         * Files whose contents aren't available are `null`.
         */
        public readonly array $files,
        public readonly array $urls,
        /**
         * Entries indicating the beginning of each span.
         */
        public readonly array $lines
    )
    {
    }
    /**
     * @param Entry[] $sourceEntries
     */
    public static function from_entries(array $source_entries): self
    {
        usort($source_entries, fn(Entry $a, Entry $b): int => $a->compare_to($b));
        $lines = [];
        // Indices associated with file urls that will be part of the source map. We
        // rely on map order so that `array_keys($url)[$urls[$u]] === $u`
        $urls = [];
        // The file for each URL, indexed by $urls' values.
        $files = [];
        $line_num = null;
        $target_entries = null;
        foreach ($source_entries as $source_entry) {
            if ($line_num === null || $source_entry->target->get_line() > $line_num) {
                $line_num = $source_entry->target->get_line();
                $target_entries = new \ArrayObject();
                $lines[] = new Target_Line_Entry($line_num, $target_entries);
            }
            $source_url = $source_entry->source->get_source_url();
            $url_id = $urls[$source_url?->to_string() ?? ''] ??= \count($urls);
            if ($source_entry->source instanceof File_Location) {
                $files[$url_id] ??= $source_entry->source->get_file();
            }
            $target_entries[] = new Target_Entry($source_entry->target->get_column(), $url_id, $source_entry->source->get_line(), $source_entry->source->get_column());
        }
        return new self(array_values(array_map(fn(int $i) => $files[$i] ?? null, $urls)), array_keys($urls), $lines);
    }
    /**
     * Encodes the Mapping mappings as a json map.
     *
     * If $includeSourceContents is `true`, this includes the source file
     * contents from {@see $files} in the map if possible.
     *
     * @return array<string, mixed>
     */
    public function to_json(bool $include_source_contents = false): array
    {
        $buff = '';
        $line = 0;
        $column = 0;
        $src_line = 0;
        $src_column = 0;
        $src_url_id = 0;
        $first = true;
        foreach ($this->lines as $entry) {
            $next_line = $entry->line;
            if ($next_line > $line) {
                for ($i = $line; $i < $next_line; $i++) {
                    $buff .= ';';
                }
                $line = $next_line;
                $column = 0;
                $first = true;
            }
            foreach ($entry->entries as $segment) {
                if (!$first) {
                    $buff .= ',';
                }
                $first = false;
                $buff .= Base64VLQ::encode($segment->column - $column);
                $column = $segment->column;
                // Encoding can be just the column offset if there is no source
                // information.
                $new_url_id = $segment->source_url_id;
                if ($new_url_id === null) {
                    continue;
                }
                \assert($segment->source_line !== null);
                \assert($segment->source_column !== null);
                $buff .= Base64VLQ::encode($new_url_id - $src_url_id);
                $src_url_id = $new_url_id;
                $buff .= Base64VLQ::encode($segment->source_line - $src_line);
                $src_line = $segment->source_line;
                $buff .= Base64VLQ::encode($segment->source_column - $src_column);
                $src_column = $segment->source_column;
            }
        }
        $result = ['version' => 3, 'sourceRoot' => $this->source_root ?? '', 'sources' => $this->urls, 'names' => [], 'mappings' => $buff];
        if ($this->target_url !== null) {
            $result['file'] = $this->target_url;
        }
        if ($include_source_contents) {
            $result['sourcesContent'] = array_map(fn(?Source_File $file) => $file?->get_text(0), $this->files);
        }
        return $result;
    }
    /**
     * Returns a new mapping with {@see $urls} transformed by $callback.
     *
     * @param callable(string): string $callback
     */
    public function map_urls(callable $callback): self
    {
        $new_urls = array_map($callback, $this->urls);
        $new = new self($this->files, $new_urls, $this->lines);
        $new->target_url = $this->target_url;
        $new->source_root = $this->source_root;
        return $new;
    }
}