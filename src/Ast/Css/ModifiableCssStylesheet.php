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
 * A modifiable version of {@see CssStylesheet} for use in the evaluation step.
 *
 * @internal
 */
final class Modifiable_Css_Stylesheet extends Modifiable_Css_Parent_Node implements Css_Stylesheet
{
    private readonly File_Span $span;
    /**
     * @param list<ModifiableCssNode> $children
     */
    public function __construct(File_Span $span, array $children = [])
    {
        parent::__construct($children);
        $this->span = $span;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Modifiable_Css_Visitor $visitor)
    {
        return $visitor->visit_css_stylesheet($this);
    }
    public function equals_ignoring_children(Modifiable_Css_Node $other): bool
    {
        return $other instanceof Modifiable_Css_Stylesheet;
    }
    public function copy_without_children(): Modifiable_Css_Stylesheet
    {
        return new Modifiable_Css_Stylesheet($this->span);
    }
}