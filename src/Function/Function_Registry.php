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

use League\Uri\Uri;
use Scss_Php\Scss_Php\Sass_Callable\Built_In_Callable;
use Scss_Php\Scss_Php\Value\Value;
/**
 * @internal
 */
class Function_Registry
{
    /**
     * @var array<string, array{overloads: array<string, callable(list<Value>): Value>, url?: string, canonical_name?: string}>
     */
    private const BUILTIN_FUNCTIONS = [
        // sass:color
        'red' => ['overloads' => ['$color' => [Color_Functions::class, 'red']], 'url' => 'sass:color'],
        'green' => ['overloads' => ['$color' => [Color_Functions::class, 'green']], 'url' => 'sass:color'],
        'blue' => ['overloads' => ['$color' => [Color_Functions::class, 'blue']], 'url' => 'sass:color'],
        'mix' => ['overloads' => ['$color1, $color2, $weight: 50%' => [Color_Functions::class, 'mix']], 'url' => 'sass:color'],
        'rgb' => ['overloads' => ['$red, $green, $blue, $alpha' => [Color_Functions::class, 'rgb'], '$red, $green, $blue' => [Color_Functions::class, 'rgb'], '$color, $alpha' => [Color_Functions::class, 'rgbTwoArgs'], '$channels' => [Color_Functions::class, 'rgbOneArgs']]],
        'rgba' => ['overloads' => ['$red, $green, $blue, $alpha' => [Color_Functions::class, 'rgba'], '$red, $green, $blue' => [Color_Functions::class, 'rgba'], '$color, $alpha' => [Color_Functions::class, 'rgbaTwoArgs'], '$channels' => [Color_Functions::class, 'rgbaOneArgs']]],
        'invert' => ['overloads' => ['$color, $weight: 100%' => [Color_Functions::class, 'invert']], 'url' => 'sass:color'],
        'hue' => ['overloads' => ['$color' => [Color_Functions::class, 'hue']], 'url' => 'sass:color'],
        'saturation' => ['overloads' => ['$color' => [Color_Functions::class, 'saturation']], 'url' => 'sass:color'],
        'lightness' => ['overloads' => ['$color' => [Color_Functions::class, 'lightness']], 'url' => 'sass:color'],
        'complement' => ['overloads' => ['$color' => [Color_Functions::class, 'complement']], 'url' => 'sass:color'],
        'hsl' => ['overloads' => ['$hue, $saturation, $lightness, $alpha' => [Color_Functions::class, 'hsl'], '$hue, $saturation, $lightness' => [Color_Functions::class, 'hsl'], '$hue, $saturation' => [Color_Functions::class, 'hslTwoArgs'], '$channels' => [Color_Functions::class, 'hslOneArgs']]],
        'hsla' => ['overloads' => ['$hue, $saturation, $lightness, $alpha' => [Color_Functions::class, 'hsla'], '$hue, $saturation, $lightness' => [Color_Functions::class, 'hsla'], '$hue, $saturation' => [Color_Functions::class, 'hslaTwoArgs'], '$channels' => [Color_Functions::class, 'hslaOneArgs']]],
        'grayscale' => ['overloads' => ['$color' => [Color_Functions::class, 'grayscale']], 'url' => 'sass:color'],
        'adjust-hue' => ['overloads' => ['$color, $degrees' => [Color_Functions::class, 'adjustHue']], 'url' => 'sass:color'],
        'lighten' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'lighten']], 'url' => 'sass:color'],
        'darken' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'darken']], 'url' => 'sass:color'],
        'saturate' => ['overloads' => ['$amount' => [Color_Functions::class, 'saturateCss'], '$color, $amount' => [Color_Functions::class, 'saturate']]],
        'desaturate' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'desaturate']], 'url' => 'sass:color'],
        'opacify' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'opacify']], 'url' => 'sass:color'],
        'fade-in' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'opacify']], 'url' => 'sass:color'],
        'transparentize' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'transparentize']], 'url' => 'sass:color'],
        'fade-out' => ['overloads' => ['$color, $amount' => [Color_Functions::class, 'transparentize']], 'url' => 'sass:color'],
        'alpha' => ['overloads' => ['$color' => [Color_Functions::class, 'alpha'], '$args...' => [Color_Functions::class, 'alphaMicrosoft']]],
        'opacity' => ['overloads' => ['$color' => [Color_Functions::class, 'opacity']], 'url' => 'sass:color'],
        'ie-hex-str' => ['overloads' => ['$color' => [Color_Functions::class, 'ieHexStr']], 'url' => 'sass:color'],
        'adjust-color' => ['overloads' => ['$color, $kwargs...' => [Color_Functions::class, 'adjust']], 'url' => 'sass:color', 'canonical_name' => 'adjust'],
        'scale-color' => ['overloads' => ['$color, $kwargs...' => [Color_Functions::class, 'scale']], 'url' => 'sass:color', 'canonical_name' => 'scale'],
        'change-color' => ['overloads' => ['$color, $kwargs...' => [Color_Functions::class, 'change']], 'url' => 'sass:color', 'canonical_name' => 'change'],
        // sass:list
        'length' => ['overloads' => ['$list' => [List_Functions::class, 'length']], 'url' => 'sass:list'],
        'nth' => ['overloads' => ['$list, $n' => [List_Functions::class, 'nth']], 'url' => 'sass:list'],
        'set-nth' => ['overloads' => ['$list, $n, $value' => [List_Functions::class, 'setNth']], 'url' => 'sass:list'],
        'join' => ['overloads' => ['$list1, $list2, $separator: auto, $bracketed: auto' => [List_Functions::class, 'join']], 'url' => 'sass:list'],
        'append' => ['overloads' => ['$list, $val, $separator: auto' => [List_Functions::class, 'append']], 'url' => 'sass:list'],
        'zip' => ['overloads' => ['$lists...' => [List_Functions::class, 'zip']], 'url' => 'sass:list'],
        'index' => ['overloads' => ['$list, $value' => [List_Functions::class, 'index']], 'url' => 'sass:list'],
        'is-bracketed' => ['overloads' => ['$list' => [List_Functions::class, 'isBracketed']], 'url' => 'sass:list'],
        'list-separator' => ['overloads' => ['$list' => [List_Functions::class, 'separator']], 'url' => 'sass:list', 'canonical_name' => 'separator'],
        // sass:map
        'map-get' => ['overloads' => ['$map, $key, $keys...' => [Map_Functions::class, 'get']], 'url' => 'sass:map', 'canonical_name' => 'get'],
        'map-merge' => ['overloads' => ['$map1, $map2' => [Map_Functions::class, 'mergeTwoArgs'], '$map1, $args...' => [Map_Functions::class, 'mergeVariadic']], 'canonical_name' => 'merge'],
        'map-remove' => ['overloads' => [
            // Because the signature below has an explicit `$key` argument, it doesn't
            // allow zero keys to be passed. We want to allow that case, so we add an
            // explicit overload for it.
            '$map' => [Map_Functions::class, 'removeNoKeys'],
            // The first argument has special handling so that the $key parameter can be
            // passed by name.
            '$map, $key, $keys...' => [Map_Functions::class, 'remove'],
        ], 'canonical_name' => 'remove'],
        'map-keys' => ['overloads' => ['$map' => [Map_Functions::class, 'keys']], 'url' => 'sass:map', 'canonical_name' => 'keys'],
        'map-values' => ['overloads' => ['$map' => [Map_Functions::class, 'values']], 'url' => 'sass:map', 'canonical_name' => 'values'],
        'map-has-key' => ['overloads' => ['$map, $key, $keys...' => [Map_Functions::class, 'hasKey']], 'url' => 'sass:map', 'canonical_name' => 'has-key'],
        // sass:math
        'abs' => ['overloads' => ['$number' => [Math_Functions::class, 'abs']], 'url' => 'sass:math'],
        'ceil' => ['overloads' => ['$number' => [Math_Functions::class, 'ceil']], 'url' => 'sass:math'],
        'floor' => ['overloads' => ['$number' => [Math_Functions::class, 'floor']], 'url' => 'sass:math'],
        'max' => ['overloads' => ['$numbers...' => [Math_Functions::class, 'max']], 'url' => 'sass:math'],
        'min' => ['overloads' => ['$numbers...' => [Math_Functions::class, 'min']], 'url' => 'sass:math'],
        'random' => ['overloads' => ['$limit: null' => [Math_Functions::class, 'random']], 'url' => 'sass:math'],
        'percentage' => ['overloads' => ['$number' => [Math_Functions::class, 'percentage']], 'url' => 'sass:math'],
        'round' => ['overloads' => ['$number' => [Math_Functions::class, 'round']], 'url' => 'sass:math'],
        'unit' => ['overloads' => ['$number' => [Math_Functions::class, 'unit']], 'url' => 'sass:math'],
        'comparable' => ['overloads' => ['$number1, $number2' => [Math_Functions::class, 'compatible']], 'url' => 'sass:math', 'canonical_name' => 'compatible'],
        'unitless' => ['overloads' => ['$number' => [Math_Functions::class, 'isUnitless']], 'url' => 'sass:math', 'canonical_name' => 'is-unitless'],
        // sass:meta
        'feature-exists' => ['overloads' => ['$feature' => [Meta_Functions::class, 'featureExists']], 'url' => 'sass:meta'],
        'inspect' => ['overloads' => ['$value' => [Meta_Functions::class, 'inspect']], 'url' => 'sass:meta'],
        'type-of' => ['overloads' => ['$value' => [Meta_Functions::class, 'typeof']], 'url' => 'sass:meta'],
        'keywords' => ['overloads' => ['$args' => [Meta_Functions::class, 'keywords']], 'url' => 'sass:meta'],
        // sass:selector
        'is-superselector' => ['overloads' => ['$super, $sub' => [Selector_Functions::class, 'isSuperselector']], 'url' => 'sass:selector'],
        'simple-selectors' => ['overloads' => ['$selector' => [Selector_Functions::class, 'simpleSelectors']], 'url' => 'sass:selector'],
        'selector-parse' => ['overloads' => ['$selector' => [Selector_Functions::class, 'parse']], 'url' => 'sass:selector', 'canonical_name' => 'parse'],
        'selector-nest' => ['overloads' => ['$selectors...' => [Selector_Functions::class, 'nest']], 'url' => 'sass:selector', 'canonical_name' => 'nest'],
        'selector-append' => ['overloads' => ['$selectors...' => [Selector_Functions::class, 'append']], 'url' => 'sass:selector', 'canonical_name' => 'append'],
        'selector-extend' => ['overloads' => ['$selector, $extendee, $extender' => [Selector_Functions::class, 'extend']], 'url' => 'sass:selector', 'canonical_name' => 'extend'],
        'selector-replace' => ['overloads' => ['$selector, $original, $replacement' => [Selector_Functions::class, 'replace']], 'url' => 'sass:selector', 'canonical_name' => 'replace'],
        'selector-unify' => ['overloads' => ['$selector1, $selector2' => [Selector_Functions::class, 'unify']], 'url' => 'sass:selector', 'canonical_name' => 'unify'],
        // sass:string
        'unquote' => ['overloads' => ['$string' => [String_Functions::class, 'unquote']], 'url' => 'sass:string'],
        'quote' => ['overloads' => ['$string' => [String_Functions::class, 'quote']], 'url' => 'sass:string'],
        'to-upper-case' => ['overloads' => ['$string' => [String_Functions::class, 'toUpperCase']], 'url' => 'sass:string'],
        'to-lower-case' => ['overloads' => ['$string' => [String_Functions::class, 'toLowerCase']], 'url' => 'sass:string'],
        'unique-id' => ['overloads' => ['' => [String_Functions::class, 'uniqueId']], 'url' => 'sass:string'],
        'str-length' => ['overloads' => ['$string' => [String_Functions::class, 'length']], 'url' => 'sass:string', 'canonical_name' => 'length'],
        'str-insert' => ['overloads' => ['$string, $insert, $index' => [String_Functions::class, 'insert']], 'url' => 'sass:string', 'canonical_name' => 'insert'],
        'str-index' => ['overloads' => ['$string, $substring' => [String_Functions::class, 'index']], 'url' => 'sass:string', 'canonical_name' => 'index'],
        'str-slice' => ['overloads' => ['$string, $start-at, $end-at: -1' => [String_Functions::class, 'slice']], 'url' => 'sass:string', 'canonical_name' => 'slice'],
        // special
        // This is only invoked using `call()`. Hand-authored `if()`s are parsed as IfExpression.
        'if' => ['overloads' => ['$condition, $if-true, $if-false' => [self::class, 'if']]],
    ];
    /**
     * Special meta functions defined directly in the {@see EvaluateVisitor} constructor
     */
    private const SPECIAL_META_GLOBAL_FUNCTIONS = ['global-variable-exists', 'variable-exists', 'function-exists', 'mixin-exists', 'content-exists', 'get-function', 'get-mixin', 'call'];
    public static function has(string $name): bool
    {
        return isset(self::BUILTIN_FUNCTIONS[$name]);
    }
    public static function get(string $name): Built_In_Callable
    {
        if (!isset(self::BUILTIN_FUNCTIONS[$name])) {
            throw new \InvalidArgumentException("There is no builtin function named {$name}.");
        }
        $url = isset(self::BUILTIN_FUNCTIONS[$name]['url']) ? Uri::new(self::BUILTIN_FUNCTIONS[$name]['url']) : null;
        $callable = Built_In_Callable::overloaded_function(self::BUILTIN_FUNCTIONS[$name]['canonical_name'] ?? $name, self::BUILTIN_FUNCTIONS[$name]['overloads'], $url);
        if (isset(self::BUILTIN_FUNCTIONS[$name]['canonical_name'])) {
            return $callable->with_name($name);
        }
        return $callable;
    }
    public static function is_builtin_function(string $name): bool
    {
        return isset(self::BUILTIN_FUNCTIONS[$name]) || \in_array($name, self::SPECIAL_META_GLOBAL_FUNCTIONS, true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function if(array $arguments): Value
    {
        return $arguments[0]->is_truthy() ? $arguments[1] : $arguments[2];
    }
}