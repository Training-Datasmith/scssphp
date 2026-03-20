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
 * An ID selector.
 *
 * This selects elements whose `id` attribute exactly matches the given name.
 *
 * @internal
 */
final class Id_Selector extends Simple_Selector
{
    public function __construct(
        /**
         * The ID name this selects for.
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
    public function get_specificity(): int
    {
        return parent::get_specificity() ** 2;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_id_selector($this);
    }
    public function add_suffix(string $suffix): \Scss_Php\Scss_Php\Ast\Selector\Id_Selector
    {
        return new Id_Selector($this->name . $suffix, $this->get_span());
    }
    public function unify(array $compound): ?array
    {
        // A given compound selector may only contain one ID.
        foreach ($compound as $simple) {
            if ($simple instanceof Id_Selector && !$simple->equals($this)) {
                return null;
            }
        }
        return parent::unify($compound);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Id_Selector && $other->name === $this->name;
    }
}