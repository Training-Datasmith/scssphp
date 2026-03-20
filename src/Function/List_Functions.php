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

use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
/**
 * @internal
 */
class List_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function length(array $arguments): Value
    {
        return Sass_Number::create(\count($arguments[0]->as_list()));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function nth(array $arguments): Value
    {
        $list = $arguments[0];
        $index = $arguments[1];
        return $list->as_list()[$list->sass_index_to_list_index($index, 'n')];
    }
    /**
     * @param list<Value> $arguments
     */
    public static function set_nth(array $arguments): Value
    {
        $list = $arguments[0];
        $index = $arguments[1];
        $value = $arguments[2];
        $new_list = $list->as_list();
        $new_list[$list->sass_index_to_list_index($index, 'n')] = $value;
        \assert(array_is_list($new_list), 'The mutation is guaranteed to affect an existing index');
        return $list->with_list_contents($new_list);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function join(array $arguments): Value
    {
        $list1 = $arguments[0];
        $list2 = $arguments[1];
        $separator_param = $arguments[2]->assert_string('separator');
        $bracketed_param = $arguments[3];
        $separator = match ($separator_param->get_text()) {
            'auto' => self::get_auto_join_separator($list1->get_separator(), $list2->get_separator()),
            'space' => List_Separator::SPACE,
            'comma' => List_Separator::COMMA,
            'slash' => List_Separator::SLASH,
            default => throw new Sass_Script_Exception('$separator: Must be "space", "comma", "slash", or "auto".'),
        };
        $bracketed = $bracketed_param instanceof Sass_String && $bracketed_param->get_text() === 'auto' ? $list1->has_brackets() : $bracketed_param->is_truthy();
        $new_list = [...$list1->as_list(), ...$list2->as_list()];
        return new Sass_List($new_list, $separator, $bracketed);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function append(array $arguments): Value
    {
        $list = $arguments[0];
        $value = $arguments[1];
        $separator_param = $arguments[2]->assert_string('separator');
        $separator = match ($separator_param->get_text()) {
            'auto' => $list->get_separator() === List_Separator::UNDECIDED ? List_Separator::SPACE : $list->get_separator(),
            'space' => List_Separator::SPACE,
            'comma' => List_Separator::COMMA,
            'slash' => List_Separator::SLASH,
            default => throw new Sass_Script_Exception('$separator: Must be "space", "comma", "slash", or "auto".'),
        };
        $new_list = [...$list->as_list(), $value];
        return $list->with_list_contents($new_list, $separator);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function zip(array $arguments): Value
    {
        $lists = array_map(fn(Value $list): array => $list->as_list(), $arguments[0]->as_list());
        if (\count($lists) === 0) {
            return Sass_List::create_empty(List_Separator::COMMA);
        }
        $i = 0;
        $results = [];
        while (Iterable_Util::every($lists, fn($list): bool => $i !== \count($list))) {
            $results[] = new Sass_List(array_map(fn(array $list): \Scss_Php\Scss_Php\Value\Value => $list[$i], $lists), List_Separator::SPACE);
            $i++;
        }
        return new Sass_List($results, List_Separator::COMMA);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function index(array $arguments): Value
    {
        $list = $arguments[0]->as_list();
        $value = $arguments[1];
        foreach ($list as $index => $item) {
            if ($item->equals($value)) {
                return Sass_Number::create($index + 1);
            }
        }
        return Sass_Null::create();
    }
    /**
     * @param list<Value> $arguments
     */
    public static function separator(array $arguments): Value
    {
        return match ($arguments[0]->get_separator()) {
            List_Separator::COMMA => new Sass_String('comma', false),
            List_Separator::SLASH => new Sass_String('slash', false),
            default => new Sass_String('space', false),
        };
    }
    /**
     * @param list<Value> $arguments
     */
    public static function is_bracketed(array $arguments): Value
    {
        return Sass_Boolean::create($arguments[0]->has_brackets());
    }
    private static function get_auto_join_separator(List_Separator $separator1, List_Separator $separator2): List_Separator
    {
        if ($separator1 === List_Separator::UNDECIDED && $separator2 === List_Separator::UNDECIDED) {
            return List_Separator::SPACE;
        }
        if ($separator1 === List_Separator::UNDECIDED) {
            return $separator2;
        }
        return $separator1;
    }
}