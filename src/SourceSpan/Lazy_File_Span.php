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
 * A wrapper for {@see FileSpan} that allows an expensive creation process to be
 * deferred until the span is actually needed.
 *
 * @internal
 */
class Lazy_File_Span implements File_Span
{
    private ?File_Span $span = null;
    /**
     * @param \Closure(): FileSpan $builder
     */
    public function __construct(
        /**
         * @readonly
         */
        private readonly \Closure $builder
    )
    {
    }
    public function get_span(): File_Span
    {
        if ($this->span === null) {
            $this->span = ($this->builder)();
        }
        return $this->span;
    }
    public function get_file(): Source_File
    {
        return $this->get_span()->get_file();
    }
    public function get_source_url(): ?Uri_Interface
    {
        return $this->get_span()->get_source_url();
    }
    public function get_length(): int
    {
        return $this->get_span()->get_length();
    }
    public function get_start(): File_Location
    {
        return $this->get_span()->get_start();
    }
    public function get_end(): File_Location
    {
        return $this->get_span()->get_end();
    }
    public function get_text(): string
    {
        return $this->get_span()->get_text();
    }
    public function union(Source_Span $other): Source_Span
    {
        return $this->get_span()->union($other);
    }
    public function compare_to(Source_Span $other): int
    {
        return $this->get_span()->compare_to($other);
    }
    public function expand(File_Span $other): File_Span
    {
        return $this->get_span()->expand($other);
    }
    public function message(string $message): string
    {
        return $this->get_span()->message($message);
    }
    public function message_multiple(string $message, string $label, array $secondary_spans): string
    {
        return $this->get_span()->message_multiple($message, $label, $secondary_spans);
    }
    public function highlight(): string
    {
        return $this->get_span()->highlight();
    }
    public function highlight_multiple(string $label, array $secondary_spans): string
    {
        return $this->get_span()->highlight_multiple($label, $secondary_spans);
    }
    public function subspan(int $start, ?int $end = null): File_Span
    {
        return $this->get_span()->subspan($start, $end);
    }
    public function get_context(): string
    {
        return $this->get_span()->get_context();
    }
}