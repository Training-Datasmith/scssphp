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
namespace Scss_Php\Scss_Php\Ast\Css;

use Scss_Php\Scss_Php\Visitor\Modifiable_Css_Visitor;
use Source_Span\File_Span;
/**
 * A modifiable version of {@see CssComment} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Comment extends Modifiable_Css_Node implements Css_Comment
{
    private readonly File_Span $span;
    public function __construct(private readonly string $text, File_Span $span)
    {
        $this->span = $span;
    }
    public function get_text(): string
    {
        return $this->text;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function is_preserved(): bool
    {
        return $this->text[2] === '!';
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_comment($this);
    }
}