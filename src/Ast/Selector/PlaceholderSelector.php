<?php

declare(strict_types=1);

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Ast\Selector;

use ScssPhp\ScssPhp\Util\Character;
use ScssPhp\ScssPhp\Visitor\SelectorVisitor;
use SourceSpan\FileSpan;

/**
 * A placeholder selector.
 *
 * This doesn't match any elements. It's intended to be extended using
 * `@extend`. It's not a plain CSS selector—it should be removed before
 * emitting a CSS document.
 *
 * @internal
 */
final class PlaceholderSelector extends SimpleSelector
{
    public function __construct(/**
     * The name of the placeholder.
     */
        private readonly string $name,
        FileSpan $span
    ) {
        parent::__construct($span);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns whether this is a private selector (that is, whether it begins
     * with `-` or `_`).
     */
    public function isPrivate(): bool
    {
        return Character::isPrivate($this->name);
    }

    public function accept(SelectorVisitor $visitor)
    {
        return $visitor->visitPlaceholderSelector($this);
    }

    public function addSuffix(string $suffix): \ScssPhp\ScssPhp\Ast\Selector\PlaceholderSelector
    {
        return new PlaceholderSelector($this->name . $suffix, $this->getSpan());
    }

    public function equals(object $other): bool
    {
        return $other instanceof PlaceholderSelector && $other->name === $this->name;
    }
}
