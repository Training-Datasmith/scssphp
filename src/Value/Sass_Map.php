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

use Scss_Php\Scss_Php\Collection\Map;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript map.
 */
final class Sass_Map extends Value
{
    /**
     * @var Map<Value>
     */
    private readonly Map $contents;
    /**
     * @param Map<Value> $contents
     */
    private function __construct(Map $contents)
    {
        $this->contents = Map::unmodifiable($contents);
    }
    public static function create_empty(): Sass_Map
    {
        return new self(new Map());
    }
    /**
     * @param Map<Value> $contents
     */
    public static function create(Map $contents): Sass_Map
    {
        return new self($contents);
    }
    /**
     * The returned Map is unmodifiable.
     *
     * @return Map<Value>
     */
    public function get_contents(): Map
    {
        return $this->contents;
    }
    public function get_separator(): List_Separator
    {
        return \count($this->contents) === 0 ? List_Separator::UNDECIDED : List_Separator::COMMA;
    }
    public function as_list(): array
    {
        $result = [];
        foreach ($this->contents as $key => $value) {
            $result[] = new Sass_List([$key, $value], List_Separator::SPACE);
        }
        return $result;
    }
    protected function get_length_as_list(): int
    {
        return \count($this->contents);
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_map($this);
    }
    public function assert_map(?string $name = null): Sass_Map
    {
        return $this;
    }
    public function try_map(): \Scss_Php\Scss_Php\Value\Sass_Map
    {
        return $this;
    }
    public function equals(object $other): bool
    {
        if ($other instanceof Sass_List) {
            return \count($this->contents) === 0 && \count($other->as_list()) === 0;
        }
        if (!$other instanceof Sass_Map) {
            return false;
        }
        if ($this->contents === $other->contents) {
            return true;
        }
        if (\count($this->contents) !== \count($other->contents)) {
            return false;
        }
        foreach ($this->contents as $key => $value) {
            $other_value = $other->contents->get($key);
            if ($other_value === null) {
                return false;
            }
            if (!$value->equals($other_value)) {
                return false;
            }
        }
        return true;
    }
}