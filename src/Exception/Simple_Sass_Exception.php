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
namespace Scss_Php\Scss_Php\Exception;

use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Error_Util;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Simple_Sass_Exception extends \Exception implements Sass_Exception
{
    private readonly File_Span $span;
    public function __construct(private readonly string $original_message, File_Span $span, ?\Throwable $previous = null)
    {
        $this->span = $span;
        parent::__construct(Error_Util::format_error_message($this->original_message, $span, $this->get_sass_trace()), 0, $previous);
    }
    public function get_original_message(): string
    {
        return $this->original_message;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function get_sass_trace(): Trace
    {
        return new Trace([Util::frame_for_span($this->span, 'root stylesheet')]);
    }
    public function with_additional_span(File_Span $span, string $label, ?\Throwable $previous = null): Multi_Span_Sass_Exception
    {
        return new Multi_Span_Sass_Exception($this->original_message, $this->span, '', [$label => $span], $previous);
    }
    public function with_trace(Trace $trace, ?\Throwable $previous = null): Sass_Runtime_Exception
    {
        return new Simple_Sass_Runtime_Exception($this->original_message, $this->span, $trace, $previous);
    }
}