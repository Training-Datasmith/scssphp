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
namespace Scss_Php\Scss_Php\Logger;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Path;
use Source_Span\File_Span;
use Source_Span\Source_Span;
/**
 * A logger that prints to a PHP stream (for instance stderr)
 */
final class Stream_Logger implements Logger_Interface
{
    /**
     * @param resource $stream          A stream resource
     * @param bool     $closeOnDestruct If true, takes ownership of the stream and close it on destruct to avoid leaks.
     */
    public function __construct(private $stream, private readonly bool $close_on_destruct = false)
    {
    }
    /**
     * @internal
     */
    public function __destruct()
    {
        if ($this->close_on_destruct) {
            fclose($this->stream);
        }
    }
    public function warn(string $message, ?Deprecation $deprecation = null, ?File_Span $span = null, ?Trace $trace = null): void
    {
        $prefix = ($deprecation !== null ? 'DEPRECATION ' : '') . 'WARNING';
        if ($span === null) {
            $formatted_message = ': ' . $message;
        } elseif ($trace !== null) {
            // If there's a span and a trace, the span's location information is
            // probably duplicated in the trace, so we just use it for highlighting.
            $formatted_message = ': ' . $message . "\n\n" . $span->highlight();
        } else {
            $formatted_message = ' on ' . $span->message("\n" . $message);
        }
        if ($trace !== null) {
            $formatted_message .= "\n" . Util::indent(rtrim($trace->get_formatted_trace()), 4);
        }
        fwrite($this->stream, $prefix . $formatted_message . "\n\n");
    }
    public function debug(string $message, Source_Span $span): void
    {
        $url = $span->get_start()->get_source_url() === null ? '-' : Path::pretty_uri($span->get_start()->get_source_url());
        $line = $span->get_start()->get_line() + 1;
        $location = "{$url}:{$line} ";
        fwrite($this->stream, \sprintf('%sDEBUG: %s', $location, $message) . "\n");
    }
}