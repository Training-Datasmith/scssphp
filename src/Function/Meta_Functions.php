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
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Value\Sass_Argument_List;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Calculation;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Function;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Mixin;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Warn;
/**
 * @internal
 */
final class Meta_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function feature_exists(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean
    {
        Warn::for_deprecation("The feature-exists() function is deprecated.\n\nMore info: https://sass-lang.com/d/feature-exists", Deprecation::featureExists);
        $feature = $arguments[0]->assert_string('feature');
        return Sass_Boolean::create(\in_array($feature->get_text(), ['global-variable-shadowing', 'extend-selector-pseudoclass', 'units-level-3', 'at-error', 'custom-property'], true));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function inspect(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        return new Sass_String((string) $arguments[0], false);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function typeof(array $arguments): \Scss_Php\Scss_Php\Value\Sass_String
    {
        $value = $arguments[0];
        return new Sass_String(match (true) {
            $value instanceof Sass_Argument_List => 'arglist',
            $value instanceof Sass_Boolean => 'bool',
            $value instanceof Sass_Color => 'color',
            $value instanceof Sass_List => 'list',
            $value instanceof Sass_Map => 'map',
            $value instanceof Sass_Null => 'null',
            $value instanceof Sass_Number => 'number',
            $value instanceof Sass_Function => 'function',
            $value instanceof Sass_Mixin => 'mixin',
            $value instanceof Sass_Calculation => 'calculation',
            $value instanceof Sass_String => 'string',
            default => throw new Sass_Script_Exception("[BUG] Unknown value type {$value}"),
        }, false);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function keywords(array $arguments): \Scss_Php\Scss_Php\Value\Sass_Map
    {
        if ($arguments[0] instanceof Sass_Argument_List) {
            $map = new Map();
            foreach ($arguments[0]->get_keywords() as $key => $value) {
                $map->put(new Sass_String($key, false), $value);
            }
            return Sass_Map::create($map);
        }
        throw Sass_Script_Exception::for_argument("{$arguments[0]} is not an argument list.", 'args');
    }
}