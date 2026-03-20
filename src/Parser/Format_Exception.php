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
namespace Scss_Php\Scss_Php\Parser;

use Jiri_Pudil\Sealed_Classes\Sealed;
use Source_Span\File_Span;
/**
 * @internal
 */
#[Sealed([Multi_Source_Format_Exception::class])]
class Format_Exception extends \Exception
{
    private readonly File_Span $span;
    public function __construct(string $message, File_Span $span, ?\Throwable $previous = null)
    {
        $this->span = $span;
        parent::__construct($message, 0, $previous);
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
}