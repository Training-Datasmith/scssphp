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

use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * A placeholder selector.
 *
 * This doesn't match any elements. It's intended to be extended using
 * `@extend`. It's not a plain CSS selector—it should be removed before
 * emitting a CSS document.
 *
 * @internal
 */
final class Placeholder_Selector extends Simple_Selector
{
    public function __construct(
        /**
         * The name of the placeholder.
         */
        private readonly string $name,
        File_Span $span
    )
    {
        parent::__construct($span);
    }
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Returns whether this is a private selector (that is, whether it begins
     * with `-` or `_`).
     */
    public function is_private(): bool
    {
        return Character::is_private($this->name);
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_placeholder_selector($this);
    }
    public function add_suffix(string $suffix): \Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector
    {
        return new Placeholder_Selector($this->name . $suffix, $this->get_span());
    }
    public function equals(object $other): bool
    {
        return $other instanceof Placeholder_Selector && $other->name === $this->name;
    }
}