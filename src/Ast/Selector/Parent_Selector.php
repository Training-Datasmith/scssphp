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
namespace Scss_Php\Scss_Php\Ast\Selector;

use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A selector that matches the parent in the Sass stylesheet.
 *
 * This is not a plain CSS selector—it should be removed before emitting a CSS
 * document.
 *
 * @internal
 */
final class Parent_Selector extends Simple_Selector
{
    public function __construct(
        File_Span $span,
        /**
         * The suffix that will be added to the parent selector after it's been
         * resolved.
         *
         * This is assumed to be a valid identifier suffix. It may be `null`,
         * indicating that the parent selector will not be modified.
         */
        private readonly ?string $suffix = null
    )
    {
        parent::__construct($span);
    }
    public function get_suffix(): ?string
    {
        return $this->suffix;
    }
    public function equals(object $other): bool
    {
        return $other === $this;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_parent_selector($this);
    }
    public function unify(array $compound): ?array
    {
        throw new \LogicException("& doesn't support unification.");
    }
}