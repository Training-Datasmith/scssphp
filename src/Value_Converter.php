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
namespace Scss_Php\Scss_Php;

use Scss_Php\Scss_Php\Collection\Map;
use Scss_Php\Scss_Php\Logger\Quiet_Logger;
use Scss_Php\Scss_Php\Node\Number;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
final class Value_Converter
{
    // Prevent instantiating it
    private function __construct()
    {
    }
    /**
     * Parses a value from a Scss source string.
     *
     * The returned value is guaranteed to be supported by the
     * Compiler methods for registering custom variables. No other
     * guarantee about it is provided. It should be considered
     * opaque values by the caller.
     */
    public static function parse_value(string $source): Value
    {
        $value = null;
        $compiler = new Compiler();
        $compiler->set_logger(new Quiet_Logger());
        $compiler->register_function('scssphp-parse-value', function (array $arguments) use (&$value): Value {
            \assert(\count($arguments) === 1);
            \assert($arguments[0] instanceof Value);
            $value = $arguments[0];
            return Sass_Null::create();
        }, ['arg']);
        $scss = <<<SCSS
        a {b: scssphp-parse-value(({$source}))}
        SCSS;
        $compiler->compile_string($scss);
        \assert($value !== null);
        return $value;
    }
    /**
     * Converts a PHP value to a Sass value
     *
     * The returned value is guaranteed to be supported by the
     * Compiler methods for registering custom variables. No other
     * guarantee about it is provided. It should be considered
     * opaque values by the caller.
     */
    public static function from_php(mixed $value): Value
    {
        if ($value instanceof Value) {
            return $value;
        }
        if ($value instanceof Number) {
            return Sass_Number::with_units($value->get_dimension(), $value->get_numerator_units(), $value->get_denominator_units());
        }
        if ($value === null) {
            return Sass_Null::create();
        }
        if ($value === true) {
            return Sass_Boolean::create(true);
        }
        if ($value === false) {
            return Sass_Boolean::create(false);
        }
        if ($value === '') {
            return new Sass_String('');
        }
        if (\is_int($value) || \is_float($value)) {
            return Sass_Number::create($value);
        }
        if (\is_string($value)) {
            return new Sass_String($value);
        }
        if (\is_array($value)) {
            if (array_is_list($value)) {
                $result = [];
                foreach ($value as $val) {
                    $result[] = self::from_php($val);
                }
                return new Sass_List($result, \count($result) > 0 ? List_Separator::COMMA : List_Separator::UNDECIDED);
            }
            /** @var Map<Value> $map */
            $map = new Map();
            foreach ($value as $key => $val) {
                $map->put(new Sass_String($key), self::from_php($val));
            }
            return Sass_Map::create($map);
        }
        throw new \InvalidArgumentException(sprintf('Cannot convert the value of type "%s" to a Sass value.', get_debug_type($value)));
    }
}