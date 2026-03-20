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
namespace Scss_Php\Scss_Php\Ast\Sass\Statement;

use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A loud CSS-style comment.
 *
 * @internal
 */
final class Loud_Comment implements Statement
{
    public function __construct(private readonly Interpolation $text)
    {
    }
    public function get_text(): Interpolation
    {
        return $this->text;
    }
    public function get_span(): File_Span
    {
        return $this->text->get_span();
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_loud_comment($this);
    }
    public function __toString(): string
    {
        return (string) $this->text;
    }
}