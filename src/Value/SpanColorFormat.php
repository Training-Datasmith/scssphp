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
namespace Scss_Php\Scss_Php\Value;

use Source_Span\File_Span;
/**
 * @internal
 */
final class Span_Color_Format implements Color_Format
{
    private readonly File_Span $span;
    public function __construct(File_Span $span)
    {
        $this->span = $span;
    }
    public function get_original(): string
    {
        return $this->span->get_text();
    }
}