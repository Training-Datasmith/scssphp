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
 * A type selector.
 *
 * This selects elements whose name equals the given name.
 *
 * @internal
 */
final class Type_Selector extends Simple_Selector
{
    public function __construct(
        /**
         * The element name being selected.
         */
        private readonly Qualified_Name $name,
        File_Span $span
    )
    {
        parent::__construct($span);
    }
    public function get_name(): Qualified_Name
    {
        return $this->name;
    }
    public function get_specificity(): int
    {
        return 1;
    }
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_type_selector($this);
    }
    public function add_suffix(string $suffix): \Scss_Php\Scss_Php\Ast\Selector\Type_Selector
    {
        return new Type_Selector(new Qualified_Name($this->name->get_name() . $suffix, $this->name->get_namespace()), $this->get_span());
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
        return array_merge([$this], $compound);
    }
    public function is_superselector(Simple_Selector $other): bool
    {
        if (parent::is_superselector($other)) {
            return true;
        }
        return $other instanceof Type_Selector && $this->name->get_name() === $other->get_name()->get_name() && ($this->name->get_namespace() === '*' || $this->name->get_namespace() === $other->get_name()->get_namespace());
    }
    public function equals(object $other): bool
    {
        return $other instanceof Type_Selector && $other->name->equals($this->name);
    }
}