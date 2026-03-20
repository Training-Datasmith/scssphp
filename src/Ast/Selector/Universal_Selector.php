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

use Scss_Php\Scss_Php\Extend\Extend_Util;
use Scss_Php\Scss_Php\Visitor\Selector_Visitor;
use Source_Span\File_Span;
/**
 * Matches any element in the given namespace.
 *
 * @internal
 */
final class Universal_Selector extends Simple_Selector
{
    public function __construct(
        File_Span $span,
        /**
         * The selector namespace.
         *
         * If this is `null`, this matches all elements in the default namespace. If
         * it's the empty string, this matches all elements that aren't in any
         * namespace. If it's `*`, this matches all elements in any namespace.
         * Otherwise, it matches all elements in the given namespace.
         */
        private readonly ?string $namespace = null
    )
    {
        parent::__construct($span);
    }
    public function get_namespace(): ?string
    {
        return $this->namespace;
    }
    public function get_specificity(): int
    {
        return 0;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_universal_selector($this);
    }
    public function unify(array $compound): ?array
    {
        $first = $compound[0] ?? null;
        if ($first instanceof Universal_Selector || $first instanceof Type_Selector) {
            $unified = Extend_Util::unify_universal_and_element($this, $first);
            if ($unified === null) {
                return null;
            }
            $compound[0] = $unified;
            return $compound;
        }
        if (\count($compound) === 1 && $first instanceof Pseudo_Selector && ($first->is_host() || $first->is_host_context())) {
            return null;
        }
        if ($this->namespace !== null && $this->namespace !== '*') {
            return array_merge([$this], $compound);
        }
        // Not-empty compound list
        if ($first !== null) {
            return $compound;
        }
        return [$this];
    }
    public function is_superselector(Simple_Selector $other): bool
    {
        if ($this->namespace === '*') {
            return true;
        }
        if ($other instanceof Type_Selector) {
            return $this->namespace === $other->get_name()->get_namespace();
        }
        if ($other instanceof Universal_Selector) {
            return $this->namespace === $other->namespace;
        }
        return $this->namespace === null || parent::is_superselector($other);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Universal_Selector && $other->namespace === $this->namespace;
    }
}