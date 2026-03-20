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
namespace Scss_Php\Scss_Php\Serializer;

use Scss_Php\Scss_Php\Source_Map\Builder\Entry;
use Scss_Php\Scss_Php\Source_Map\Single_Mapping;
use Scss_Php\Scss_Php\Util\List_Util;
use Source_Span\File_Location;
use Source_Span\File_Span;
use Source_Span\Simple_Source_Location;
use Source_Span\Source_Location;
use Source_Span\Source_Span;
/**
 * A {@see SourceMapBuffer} that builds a source map.
 *
 * @internal
 */
final class Tracking_Source_Map_Buffer implements Source_Map_Buffer
{
    private readonly String_Buffer $buffer;
    /**
     * @var list<Entry>
     */
    private array $entries = [];
    /**
     * The index of the current line in {@see $buffer}.
     */
    private int $line = 0;
    /**
     * The index of the current column in {@see $buffer}.
     */
    private int $column = 0;
    /**
     * Whether the text currently being written should be encompassed by a
     * {@see SourceSpan}.
     */
    private bool $in_span = false;
    public function __construct()
    {
        $this->buffer = new Simple_String_Buffer();
    }
    public function get_length(): int
    {
        return $this->buffer->get_length();
    }
    /**
     * The current location in {@see $buffer}.
     */
    private function get_target_location(): Source_Location
    {
        return new Simple_Source_Location($this->buffer->get_length(), line: $this->line, column: $this->column);
    }
    public function for_span(File_Span $span, callable $callback)
    {
        $was_in_span = $this->in_span;
        $this->in_span = true;
        $this->add_entry($span->get_start(), $this->get_target_location());
        try {
            return $callback();
        } finally {
            // We could map $span->getEnd() to $this->getTargetLocation() here, but in practice
            // browsers don't care about where a span ends as long as it covers at
            // least the entity that they're looking up. Avoiding end mappings halves
            // the size of the source maps we generate.
            $this->in_span = $was_in_span;
        }
    }
    /**
     * Adds an entry to {@see $entries} unless it's redundant with the last entry.
     */
    private function add_entry(File_Location $source, Source_Location $target): void
    {
        if ($this->entries !== []) {
            $entry = List_Util::last($this->entries);
            // Browsers don't care about the position of a value within a line, so
            // it's redundant to have two entries on the same target line that both
            // point to the same source line, even if they point to different
            // columns in that line.
            if ($entry->source->get_line() === $source->get_line() && $entry->target->get_line() === $target->get_line()) {
                return;
            }
            // Since source maps are only used to look up the source from the target
            // and not vice versa, we don't need multiple mappings to the same target.
            if ($entry->target->get_offset() === $target->get_offset()) {
                return;
            }
        }
        $this->entries[] = new Entry($source, $target);
    }
    public function write(string $string): void
    {
        $this->buffer->write($string);
        for ($i = 0; $i < \strlen($string); ++$i) {
            if ($string[$i] === "\n") {
                $this->write_line();
            } else {
                $this->column++;
            }
        }
    }
    public function write_char(string $char): void
    {
        $this->buffer->write_char($char);
        if ($char === "\n") {
            $this->write_line();
        } else {
            $this->column++;
        }
    }
    /**
     * Records that a line has been passed.
     *
     * If we're in the middle of a source span, indicate that at the beginning of
     * the new line. This is necessary because source maps consider each line
     * separately.
     */
    private function write_line(): void
    {
        $last_entry = List_Util::last($this->entries);
        // Trim useless entries.
        if ($last_entry->target->get_line() === $this->line && $last_entry->target->get_column() === $this->column) {
            array_pop($this->entries);
        }
        $this->line++;
        $this->column = 0;
        if ($this->in_span) {
            $this->entries[] = new Entry($last_entry->source, $this->get_target_location());
        }
    }
    public function __toString(): string
    {
        return (string) $this->buffer;
    }
    public function build_source_map(?string $prefix): Single_Mapping
    {
        if ($prefix === null || $prefix === '') {
            return Single_Mapping::from_entries($this->entries);
        }
        $prefix_length = \strlen($prefix);
        $prefix_lines = 0;
        $prefix_column = 0;
        for ($i = 0; $i < \strlen($prefix); ++$i) {
            if ($prefix[$i] === "\n") {
                $prefix_lines++;
                $prefix_column = 0;
            } else {
                $prefix_column++;
            }
        }
        return Single_Mapping::from_entries(array_map(fn(Entry $entry): \Scss_Php\Scss_Php\Source_Map\Builder\Entry => new Entry($entry->source, new Simple_Source_Location(
            $entry->target->get_offset() + $prefix_length,
            line: $entry->target->get_line() + $prefix_lines,
            // Only adjust the column for entries that are on the same line as
            // the last chunk of the prefix.
            column: $entry->target->get_column() + ($entry->target->get_line() === 0 ? $prefix_column : 0)
        )), $this->entries));
    }
}