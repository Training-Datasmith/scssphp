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
namespace Scss_Php\Scss_Php\Source_Span;

use League\Uri\Contracts\Uri_Interface;
use Source_Span\File_Location;
use Source_Span\File_Span;
use Source_Span\Source_File;
use Source_Span\Source_Span;
/**
 * A FileSpan wrapper that with secondary spans attached, so that
 * {@see MultiSpan::message} can forward to {@see SourceSpan::messageMultiple}.
 *
 * This is used to transparently support multi-span messages in situations that
 * need to be backwards-compatible with single spans, such as logger
 * invocations. To match the `source_span` package, separate APIs should
 * generally be preferred over this class wherever backwards compatibility
 * isn't a concern.
 *
 * @internal
 */
final class Multi_Span implements File_Span
{
    /**
     * @param array<string, SourceSpan> $secondarySpans
     */
    public function __construct(private readonly File_Span $primary, private readonly string $primary_label, private readonly array $secondary_spans)
    {
    }
    public function get_start(): File_Location
    {
        return $this->primary->get_start();
    }
    public function get_end(): File_Location
    {
        return $this->primary->get_end();
    }
    public function get_text(): string
    {
        return $this->primary->get_text();
    }
    public function get_context(): string
    {
        return $this->primary->get_context();
    }
    public function get_file(): Source_File
    {
        return $this->primary->get_file();
    }
    public function get_length(): int
    {
        return $this->primary->get_length();
    }
    public function get_source_url(): ?Uri_Interface
    {
        return $this->primary->get_source_url();
    }
    public function compare_to(Source_Span $other): int
    {
        return $this->primary->compare_to($other);
    }
    public function expand(File_Span $other): File_Span
    {
        return $this->with_primary($this->primary->expand($other));
    }
    public function union(Source_Span $other): Source_Span
    {
        return $this->primary->union($other);
    }
    public function subspan(int $start, ?int $end = null): File_Span
    {
        return $this->with_primary($this->primary->subspan($start, $end));
    }
    public function highlight(): string
    {
        return $this->primary->highlight_multiple($this->primary_label, $this->secondary_spans);
    }
    public function message(string $message): string
    {
        return $this->primary->message_multiple($message, $this->primary_label, $this->secondary_spans);
    }
    public function highlight_multiple(string $label, array $secondary_spans): string
    {
        return $this->primary->highlight_multiple($label, array_merge($this->secondary_spans, $secondary_spans));
    }
    public function message_multiple(string $message, string $label, array $secondary_spans): string
    {
        return $this->primary->message_multiple($message, $label, array_merge($this->secondary_spans, $secondary_spans));
    }
    /**
     * Returns a copy of $this with $newPrimary as its primary span.
     */
    private function with_primary(File_Span $new_primary): Multi_Span
    {
        return new Multi_Span($new_primary, $this->primary_label, $this->secondary_spans);
    }
}