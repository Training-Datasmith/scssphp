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
namespace Scss_Php\Scss_Php\Value;

use Jiri_Pudil\Sealed_Classes\Sealed;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript list.
 */
#[Sealed(permits: [Sass_Argument_List::class])]
class Sass_List extends Value
{
    /**
     * @var list<Value>
     */
    private readonly array $contents;
    private readonly List_Separator $separator;
    public static function create_empty(List_Separator $separator = List_Separator::UNDECIDED, bool $brackets = false): Sass_List
    {
        return new self([], $separator, $brackets);
    }
    /**
     * @param list<Value> $contents
     */
    public function __construct(array $contents, List_Separator $separator, private readonly bool $brackets = false)
    {
        if ($separator === List_Separator::UNDECIDED && count($contents) > 1) {
            throw new \InvalidArgumentException('A list with more than one element must have an explicit separator.');
        }
        $this->contents = $contents;
        $this->separator = $separator;
    }
    public function get_separator(): List_Separator
    {
        return $this->separator;
    }
    public function has_brackets(): bool
    {
        return $this->brackets;
    }
    public function is_blank(): bool
    {
        if ($this->brackets) {
            return false;
        }
        foreach ($this->contents as $element) {
            if (!$element->is_blank()) {
                return false;
            }
        }
        return true;
    }
    public function as_list(): array
    {
        return $this->contents;
    }
    protected function get_length_as_list(): int
    {
        return \count($this->contents);
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_list($this);
    }
    public function assert_map(?string $name = null): Sass_Map
    {
        if (\count($this->contents) === 0) {
            return Sass_Map::create_empty();
        }
        return parent::assert_map($name);
    }
    public function try_map(): ?Sass_Map
    {
        if (\count($this->contents) === 0) {
            return Sass_Map::create_empty();
        }
        return null;
    }
    public function equals(object $other): bool
    {
        if ($other instanceof Sass_Map) {
            return \count($this->contents) === 0 && \count($other->as_list()) === 0;
        }
        if (!$other instanceof Sass_List) {
            return false;
        }
        if ($this->separator !== $other->separator || $this->brackets !== $other->brackets) {
            return false;
        }
        $other_content = $other->contents;
        $length = \count($this->contents);
        if ($length !== \count($other_content)) {
            return false;
        }
        for ($i = 0; $i < $length; ++$i) {
            if (!$this->contents[$i]->equals($other_content[$i])) {
                return false;
            }
        }
        return true;
    }
}