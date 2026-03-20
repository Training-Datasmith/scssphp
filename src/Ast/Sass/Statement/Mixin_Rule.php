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

use Scss_Php\Scss_Php\Ast\Sass\Sass_Declaration;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A mixin declaration.
 *
 * This declares a mixin that's invoked using `@include`.
 *
 * @internal
 */
final class Mixin_Rule extends Callable_Declaration implements Sass_Declaration
{
    /**
     * Whether the mixin contains a `@content` rule.
     */
    private ?bool $content = null;
    public function has_content(): bool
    {
        if (!isset($this->content)) {
            $this->content = (new Has_Content_Visitor())->visit_mixin_rule($this) === true;
        }
        return $this->content;
    }
    public function get_name_span(): File_Span
    {
        $start_span = $this->get_span()->get_text()[0] === '=' ? Span_Util::trim_left($this->get_span()->subspan(1)) : Span_Util::without_initial_at_rule($this->get_span());
        return Span_Util::initial_identifier($start_span);
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_mixin_rule($this);
    }
    public function __toString(): string
    {
        $buffer = '@mixin ' . $this->get_name();
        if (!$this->get_arguments()->is_empty()) {
            $buffer .= "({$this->get_arguments()})";
        }
        return $buffer . (' {' . implode(' ', $this->get_children()) . '}');
    }
}