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
namespace Scss_Php\Scss_Php\Function;

use Scss_Php\Scss_Php\Collection\Map;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Value;
/**
 * @internal
 */
class Map_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function get(array $arguments): Value
    {
        $map = $arguments[0]->assert_map('map');
        $keys = [$arguments[1], ...$arguments[2]->as_list()];
        foreach (List_Util::except_last($keys) as $key) {
            $value = $map->get_contents()->get($key);
            if (!$value instanceof Sass_Map) {
                return Sass_Null::create();
            }
            $map = $value;
        }
        return $map->get_contents()->get(List_Util::last($keys)) ?? Sass_Null::create();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function merge_two_args(array $arguments): Value
    {
        $map1 = $arguments[0]->assert_map('map1');
        $map2 = $arguments[1]->assert_map('map2');
        $result = Map::of($map1->get_contents());
        foreach ($map2->get_contents() as $key => $value) {
            $result->put($key, $value);
        }
        return Sass_Map::create($result);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function merge_variadic(array $arguments): Value
    {
        $map1 = $arguments[0]->assert_map('map1');
        $args = $arguments[1]->as_list();
        if ($args === []) {
            throw new Sass_Script_Exception('Expected $args to contain a key.');
        }
        if (\count($args) === 1) {
            throw new Sass_Script_Exception('Expected $args to contain a map.');
        }
        $keys = List_Util::except_last($args);
        $map2 = List_Util::last($args)->assert_map('map2');
        return self::modify($map1, $keys, function (Value $old_value) use ($map2) {
            $nested_map = $old_value->try_map();
            if ($nested_map === null) {
                return $map2;
            }
            $result = Map::of($nested_map->get_contents());
            foreach ($map2->get_contents() as $key => $value) {
                $result->put($key, $value);
            }
            return Sass_Map::create($result);
        });
    }
    /**
     * @param list<Value> $arguments
     */
    public static function remove_no_keys(array $arguments): Value
    {
        return $arguments[0]->assert_map('map');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function remove(array $arguments): Value
    {
        $map = $arguments[0]->assert_map('map');
        $keys = [$arguments[1], ...$arguments[2]->as_list()];
        $mutable_map = Map::of($map->get_contents());
        foreach ($keys as $key) {
            $mutable_map->remove($key);
        }
        return Sass_Map::create($mutable_map);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function keys(array $arguments): Value
    {
        return new Sass_List($arguments[0]->assert_map('map')->get_contents()->keys(), List_Separator::COMMA);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function values(array $arguments): Value
    {
        return new Sass_List($arguments[0]->assert_map('map')->get_contents()->values(), List_Separator::COMMA);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function has_key(array $arguments): Value
    {
        $map = $arguments[0]->assert_map('map');
        $keys = [$arguments[1], ...$arguments[2]->as_list()];
        foreach (List_Util::except_last($keys) as $key) {
            $value = $map->get_contents()->get($key);
            if (!$value instanceof Sass_Map) {
                return Sass_Boolean::create(false);
            }
            $map = $value;
        }
        return Sass_Boolean::create($map->get_contents()->contains_key(List_Util::last($keys)));
    }
    /**
     * Updates the specified value in $map by applying the $modify callback to
     * it, then returns the resulting map.
     *
     * If more than one key is provided, this means the map targeted for update is
     * nested within $map. The multiple $keys form a path of nested maps that
     * leads to the targeted value, which is passed to $modify.
     *
     * If any value along the path (other than the last one) is not a map and
     * $addNesting is `true`, this creates nested maps to match $keys and passes
     * {@see SassNull} to $modify. Otherwise, this fails and returns $map with no
     * changes.
     *
     * If no keys are provided, this passes $map directly to modify and returns
     * the result.
     *
     * @param Value[] $keys
     * @param callable(Value $old): Value $modify
     *
     * @param-immediately-invoked-callable $modify
     */
    private static function modify(Sass_Map $map, array $keys, callable $modify, bool $add_nesting = true): Value
    {
        $iterator = new \ArrayIterator($keys);
        $modify_nested_map = function (Sass_Map $map) use ($iterator, $modify, $add_nesting, &$modify_nested_map): Sass_Map {
            $mutable_map = Map::of($map->get_contents());
            $key = $iterator->current();
            $iterator->next();
            if (!$iterator->valid()) {
                $mutable_map->put($key, $modify($mutable_map->get($key) ?? Sass_Null::create()));
                return Sass_Map::create($mutable_map);
            }
            $nested_map = $mutable_map->get($key)?->try_map();
            if ($nested_map === null && !$add_nesting) {
                return Sass_Map::create($mutable_map);
            }
            $mutable_map->put($key, $modify_nested_map($nested_map ?? Sass_Map::create_empty()));
            return Sass_Map::create($mutable_map);
        };
        $iterator->rewind();
        return $iterator->valid() ? $modify_nested_map($map) : $modify($map);
    }
}