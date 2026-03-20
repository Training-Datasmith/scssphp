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
 * A class selector.
 *
 * This selects elements whose `class` attribute contains an identifier with
 * the given name.
 *
 * @internal
 */
final class Class_Selector extends Simple_Selector
{
    public function __construct(
        /**
         * The class name this selects for.
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
    public function accept(Selector_Visitor $visitor)
    {
        return $visitor->visit_class_selector($this);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Class_Selector && $other->name === $this->name;
    }
    public function add_suffix(string $suffix): \Scss_Php\Scss_Php\Ast\Selector\Class_Selector
    {
        return new Class_Selector($this->name . $suffix, $this->get_span());
    }
}