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

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Value\Color_Format_Enum;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Argument_List;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Warn;
/**
 * @internal
 */
class Color_Functions
{
    /**
     * @param list<Value> $arguments
     */
    public static function rgb(array $arguments): Value
    {
        return self::rgb_impl('rgb', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function rgb_two_args(array $arguments): Value
    {
        return self::rgb_two_args_impl('rgb', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function rgb_one_args(array $arguments): Value
    {
        $parsed = self::parse_channels('rgb', ['$red', '$green', '$blue'], $arguments[0]);
        return $parsed instanceof Sass_String ? $parsed : self::rgb_impl('rgb', $parsed);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function rgba(array $arguments): Value
    {
        return self::rgb_impl('rgba', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function rgba_two_args(array $arguments): Value
    {
        return self::rgb_two_args_impl('rgba', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function rgba_one_args(array $arguments): Value
    {
        $parsed = self::parse_channels('rgba', ['$red', '$green', '$blue'], $arguments[0]);
        return $parsed instanceof Sass_String ? $parsed : self::rgb_impl('rgba', $parsed);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function invert(array $arguments): Value
    {
        $weight = $arguments[1]->assert_number('weight');
        if ($arguments[0] instanceof Sass_Number || $arguments[0]->is_special_number()) {
            if ($weight->get_value() !== 100.0 || !$weight->has_unit('%')) {
                throw new Sass_Script_Exception('Only one argument may be passed to the plain-CSS invert() function.');
            }
            // Use the native CSS `invert` filter function.
            return self::function_string('invert', [$arguments[0]]);
        }
        $color = $arguments[0]->assert_color('color');
        $inverse = $color->change_rgb(255 - $color->get_red(), 255 - $color->get_green(), 255 - $color->get_blue());
        return self::mix_colors($inverse, $color, $weight);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsl(array $arguments): Value
    {
        return self::hsl_impl('hsl', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsl_two_args(array $arguments): Value
    {
        // hsl(123, var(--foo)) is valid CSS because --foo might be `10%, 20%` and
        // functions are parsed after variable substitution.
        if ($arguments[0]->is_var() || $arguments[1]->is_var()) {
            return self::function_string('hsl', $arguments);
        }
        throw new Sass_Script_Exception('Missing argument $lightness.');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsl_one_args(array $arguments): Value
    {
        $parsed = self::parse_channels('hsl', ['$hue', '$saturation', '$lightness'], $arguments[0]);
        return $parsed instanceof Sass_String ? $parsed : self::hsl_impl('hsl', $parsed);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsla(array $arguments): Value
    {
        return self::hsl_impl('hsla', $arguments);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsla_two_args(array $arguments): Value
    {
        // hsl(123, var(--foo)) is valid CSS because --foo might be `10%, 20%` and
        // functions are parsed after variable substitution.
        if ($arguments[0]->is_var() || $arguments[1]->is_var()) {
            return self::function_string('hsla', $arguments);
        }
        throw new Sass_Script_Exception('Missing argument $lightness.');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hsla_one_args(array $arguments): Value
    {
        $parsed = self::parse_channels('hsla', ['$hue', '$saturation', '$lightness'], $arguments[0]);
        return $parsed instanceof Sass_String ? $parsed : self::hsl_impl('hsla', $parsed);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function grayscale(array $arguments): Value
    {
        if ($arguments[0] instanceof Sass_Number || $arguments[0]->is_special_number()) {
            // Use the native CSS `grayscale` filter function.
            return self::function_string('grayscale', $arguments);
        }
        $color = $arguments[0]->assert_color('color');
        return $color->change_hsl(saturation: 0);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function adjust_hue(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        $degrees = self::angle_value($arguments[1], 'degrees');
        return $color->change_hsl(hue: $color->get_hue() + $degrees);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function lighten(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_hsl(lightness: Number_Util::clamp($color->get_lightness() + $amount->value_in_range(0, 100, 'amount'), 0, 100));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function darken(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_hsl(lightness: Number_Util::clamp($color->get_lightness() - $amount->value_in_range(0, 100, 'amount'), 0, 100));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function saturate_css(array $arguments): Value
    {
        if ($arguments[0] instanceof Sass_Number || $arguments[0]->is_special_number()) {
            // Use the native CSS `saturate` filter function.
            return self::function_string('saturate', $arguments);
        }
        $number = $arguments[0]->assert_number('amount');
        return new Sass_String('saturate(' . $number->to_css_string() . ')', false);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function saturate(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_hsl(saturation: Number_Util::clamp($color->get_saturation() + $amount->value_in_range(0, 100, 'amount'), 0, 100));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function desaturate(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_hsl(saturation: Number_Util::clamp($color->get_saturation() - $amount->value_in_range(0, 100, 'amount'), 0, 100));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function alpha(array $arguments): Value
    {
        $argument = $arguments[0];
        if ($argument instanceof Sass_String && !$argument->has_quotes() && preg_match('/^[a-zA-Z]+\s*=/', $argument->get_text())) {
            // Support the proprietary Microsoft alpha() function.
            return self::function_string('alpha', $arguments);
        }
        $color = $arguments[0]->assert_color('color');
        return Sass_Number::create($color->get_alpha());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function alpha_microsoft(array $arguments): Value
    {
        $arg_list = $arguments[0]->as_list();
        $argument_count = \count($arg_list);
        if ($argument_count > 0 && Iterable_Util::every($arg_list, fn($argument): bool => $argument instanceof Sass_String && !$argument->has_quotes() && preg_match('/^[a-zA-Z]+\s*=/', $argument->get_text()))) {
            // Support the proprietary Microsoft alpha() function.
            return self::function_string('alpha', $arguments);
        }
        \assert($argument_count !== 1);
        if ($argument_count === 0) {
            throw new Sass_Script_Exception('Missing argument $color.');
        }
        throw new Sass_Script_Exception("Only 1 argument allowed, but {$argument_count} were passed.");
    }
    /**
     * @param list<Value> $arguments
     */
    public static function opacity(array $arguments): Value
    {
        if ($arguments[0] instanceof Sass_Number || $arguments[0]->is_special_number()) {
            // Use the native CSS `opacity` filter function.
            return self::function_string('opacity', $arguments);
        }
        $color = $arguments[0]->assert_color('color');
        return Sass_Number::create($color->get_alpha());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function red(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_red());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function green(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_green());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function blue(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_blue());
    }
    /**
     * @param list<Value> $arguments
     */
    public static function mix(array $arguments): Value
    {
        $color1 = $arguments[0]->assert_color('color1');
        $color2 = $arguments[1]->assert_color('color2');
        $weight = $arguments[2]->assert_number('weight');
        return self::mix_colors($color1, $color2, $weight);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function hue(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_hue(), 'deg');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function saturation(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_saturation(), '%');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function lightness(array $arguments): Value
    {
        return Sass_Number::create($arguments[0]->assert_color('color')->get_lightness(), '%');
    }
    /**
     * @param list<Value> $arguments
     */
    public static function complement(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        return $color->change_hsl(hue: $color->get_hue() + 180);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function adjust(array $arguments): Value
    {
        return self::update_components($arguments, adjust: true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function scale(array $arguments): Value
    {
        return self::update_components($arguments, scale: true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function change(array $arguments): Value
    {
        return self::update_components($arguments, change: true);
    }
    /**
     * @param list<Value> $arguments
     */
    public static function ie_hex_str(array $arguments): Value
    {
        $color = $arguments[0]->assert_color('color');
        return new Sass_String('#' . self::hex_string(Number_Util::fuzzy_round($color->get_alpha() * 255)) . self::hex_string($color->get_red()) . self::hex_string($color->get_green()) . self::hex_string($color->get_blue()), false);
    }
    private static function hex_string(int $component): string
    {
        return strtoupper(str_pad(dechex($component), 2, '0', STR_PAD_LEFT));
    }
    /**
     * @param list<Value> $arguments
     */
    private static function update_components(array $arguments, bool $change = false, bool $adjust = false, bool $scale = false): Sass_Color
    {
        \assert(\count(array_filter([$change, $adjust, $scale])) === 1);
        $color = $arguments[0]->assert_color('color');
        $argument_list = $arguments[1];
        \assert($argument_list instanceof Sass_Argument_List);
        if (\count($argument_list->as_list()) > 0) {
            throw new Sass_Script_Exception('Only one positional argument is allowed. All other arguments must be passed by name.');
        }
        $keywords = $argument_list->get_keywords();
        $get_param = function (string $name, float $max, bool $check_percent = false, bool $assert_percent = false, bool $check_unitless = false) use (&$keywords, $change, $scale): ?float {
            $number = ($keywords[$name] ?? null)?->assert_number($name);
            unset($keywords[$name]);
            if ($number === null) {
                return null;
            }
            if (!$scale && $check_unitless) {
                if ($number->has_units()) {
                    Warn::for_deprecation(<<<TXT
                    \${$name}: Passing a number with unit {$number->get_unit_string()} is deprecated.
                    
                    To preserve current behavior: {$number->unit_suggestion($name)}
                    
                    More info: https://sass-lang.com/d/function-units
                    TXT, Deprecation::functionUnits);
                }
            }
            if (!$scale && $check_percent) {
                self::check_percent($number, $name);
            }
            if ($scale || $assert_percent) {
                $number->assert_unit('%', $name);
            }
            if ($scale) {
                $max = 100;
            }
            return $scale || $assert_percent ? $number->value_in_range($change ? 0 : -$max, $max, $name) : $number->value_in_range_with_unit($change ? 0 : -$max, $max, $name, $check_percent ? '%' : '');
        };
        $alpha = $get_param('alpha', 1, checkUnitless: true);
        $red = $get_param('red', 255);
        $green = $get_param('green', 255);
        $blue = $get_param('blue', 255);
        if ($scale) {
            $hue = null;
        } else {
            $hue_value = $keywords['hue'] ?? null;
            unset($keywords['hue']);
            $hue = $hue_value === null ? null : self::angle_value($hue_value, 'hue');
        }
        $saturation = $get_param('saturation', 100, checkPercent: true);
        $lightness = $get_param('lightness', 100, checkPercent: true);
        $whiteness = $get_param('whiteness', 100, assertPercent: true);
        $blackness = $get_param('blackness', 100, assertPercent: true);
        if (\count($keywords) > 0) {
            throw new Sass_Script_Exception(sprintf('No %s named %s.', String_Util::pluralize('argument', \count($keywords)), String_Util::to_sentence(array_map(fn($name): string => "\${$name}", array_keys($keywords)), 'or')));
        }
        $has_rgb = $red !== null || $green !== null || $blue !== null;
        $has_sl = $saturation !== null || $lightness !== null;
        $has_wb = $whiteness !== null || $blackness !== null;
        if ($has_rgb && ($has_sl || $has_wb || $hue !== null)) {
            $format = $has_wb ? 'HWB' : 'HSL';
            throw new Sass_Script_Exception("RGB parameters may not be passed along with {$format} parameters.");
        }
        if ($has_sl && $has_wb) {
            throw new Sass_Script_Exception('HSL parameters may not be passed along with HWB parameters.');
        }
        $update_value = function (float $current, ?float $param, float $max) use ($change, $adjust): float {
            if ($param === null) {
                return $current;
            }
            if ($change) {
                return $param;
            }
            if ($adjust) {
                return Number_Util::clamp($current + $param, 0, $max);
            }
            return $current + ($param > 0 ? $max - $current : $current) * $param / 100;
        };
        $update_rgb = fn(int $current, ?float $param): int => Number_Util::fuzzy_round($update_value($current, $param, 255));
        if ($has_rgb) {
            return $color->change_rgb($update_rgb($color->get_red(), $red), $update_rgb($color->get_green(), $green), $update_rgb($color->get_blue(), $blue), $update_value($color->get_alpha(), $alpha, 1));
        }
        if ($has_wb) {
            return $color->change_hwb($change ? $hue : $color->get_hue() + ($hue ?? 0), $update_value($color->get_whiteness(), $whiteness, 100), $update_value($color->get_blackness(), $blackness, 100), $update_value($color->get_alpha(), $alpha, 1));
        }
        if ($hue !== null || $has_sl) {
            return $color->change_hsl($change ? $hue : $color->get_hue() + ($hue ?? 0), $update_value($color->get_saturation(), $saturation, 100), $update_value($color->get_lightness(), $lightness, 100), $update_value($color->get_alpha(), $alpha, 1));
        }
        if ($alpha !== null) {
            return $color->change_alpha($update_value($color->get_alpha(), $alpha, 1));
        }
        return $color;
    }
    /**
     * Returns a string representation of $name called with $arguments, as though
     * it were a plain CSS function.
     *
     * @param Value[] $arguments
     */
    private static function function_string(string $name, array $arguments): Sass_String
    {
        return new Sass_String($name . '(' . implode(', ', array_map(fn(Value $argument): string => $argument->to_css_string(), $arguments)) . ')', false);
    }
    /**
     * @param list<Value> $arguments
     */
    private static function rgb_impl(string $name, array $arguments): Value
    {
        $alpha = $arguments[3] ?? null;
        if ($arguments[0]->is_special_number() || $arguments[1]->is_special_number() || $arguments[2]->is_special_number() || ($alpha?->is_special_number() ?? false)) {
            return self::function_string($name, $arguments);
        }
        $red = $arguments[0]->assert_number('red');
        $green = $arguments[1]->assert_number('green');
        $blue = $arguments[2]->assert_number('blue');
        return Sass_Color::rgb_internal(Number_Util::fuzzy_round(self::percentage_or_unitless($red, 255, 'red')), Number_Util::fuzzy_round(self::percentage_or_unitless($green, 255, 'green')), Number_Util::fuzzy_round(self::percentage_or_unitless($blue, 255, 'blue')), $alpha !== null ? self::percentage_or_unitless($alpha->assert_number('alpha'), 1, 'alpha') : 1, Color_Format_Enum::rgbFunction);
    }
    /**
     * @param list<Value> $arguments
     */
    private static function rgb_two_args_impl(string $name, array $arguments): Value
    {
        // rgba(var(--foo), 0.5) is valid CSS because --foo might be `123, 456, 789`
        // and functions are parsed after variable substitution.
        if ($arguments[0]->is_var() || !$arguments[0] instanceof Sass_Color && $arguments[1]->is_var()) {
            return self::function_string($name, $arguments);
        }
        if ($arguments[1]->is_special_number()) {
            $color = $arguments[0]->assert_color('color');
            return new Sass_String("{$name}({$color->get_red()}, {$color->get_green()}, {$color->get_blue()}, {$arguments[1]->to_css_string()})", false);
        }
        $color = $arguments[0]->assert_color('color');
        $alpha = $arguments[1]->assert_number('alpha');
        return $color->change_alpha(self::percentage_or_unitless($alpha, 1, 'alpha'));
    }
    /**
     * @param list<Value> $arguments
     */
    private static function hsl_impl(string $name, array $arguments): Value
    {
        $alpha = $arguments[3] ?? null;
        if ($arguments[0]->is_special_number() || $arguments[1]->is_special_number() || $arguments[2]->is_special_number() || ($alpha?->is_special_number() ?? false)) {
            return self::function_string($name, $arguments);
        }
        $hue = self::angle_value($arguments[0], 'hue');
        $saturation = $arguments[1]->assert_number('saturation');
        $lightness = $arguments[2]->assert_number('lightness');
        self::check_percent($saturation, 'saturation');
        self::check_percent($lightness, 'lightness');
        return Sass_Color::hsl_internal($hue, Number_Util::clamp($saturation->get_value(), 0, 100), Number_Util::clamp($lightness->get_value(), 0, 100), $alpha !== null ? self::percentage_or_unitless($alpha->assert_number('alpha'), 1, 'alpha') : 1, Color_Format_Enum::hslFunction);
    }
    /**
     * Asserts that $angle is a number and returns its value in degrees.
     *
     * Prints a deprecation warning if $angle has a non-angle unit.
     */
    private static function angle_value(Value $angle_value, string $name): float
    {
        $angle = $angle_value->assert_number($name);
        if ($angle->compatible_with_unit('deg')) {
            return $angle->coerce_value_to_unit('deg');
        }
        Warn::for_deprecation(<<<TXT
        \${$name}: Passing a unit other than deg ({$angle}) is deprecated.
        
        To preserve current behavior: {$angle->unit_suggestion($name)}
        
        See https://sass-lang.com/d/function-units
        TXT, Deprecation::functionUnits);
        return $angle->get_value();
    }
    private static function check_percent(Sass_Number $number, string $name): void
    {
        if ($number->has_unit('%')) {
            return;
        }
        Warn::for_deprecation(<<<TXT
        \${$name}: Passing a number without unit % ({$number}) is deprecated.
        
        To preserve current behavior: {$number->unit_suggestion($name, '%')}
        
        More info: https://sass-lang.com/d/function-units
        TXT, Deprecation::functionUnits);
    }
    /**
     * @param list<string> $argumentNames
     *
     * @return SassString|list<Value>
     */
    private static function parse_channels(string $name, array $argument_names, Value $channels): Sass_String|array
    {
        if ($channels->is_var()) {
            return self::function_string($name, [$channels]);
        }
        $original_channels = $channels;
        $alpha_from_slash_list = null;
        if ($channels->get_separator() === List_Separator::SLASH) {
            $list = $channels->as_list();
            if (\count($list) !== 2) {
                throw new Sass_Script_Exception(sprintf('Only 2 slash-separated elements allowed, but %s %s passed.', \count($list), String_Util::pluralize('was', \count($list), 'were')));
            }
            $channels = $list[0];
            $alpha_from_slash_list = $list[1];
            if (!$alpha_from_slash_list->is_special_number()) {
                $alpha_from_slash_list->assert_number('alpha');
            }
            if ($list[0]->is_var()) {
                return self::function_string($name, [$original_channels]);
            }
        }
        $is_comma_separated = $channels->get_separator() === List_Separator::COMMA;
        $is_bracketed = $channels->has_brackets();
        if ($is_comma_separated || $is_bracketed) {
            $buffer = '$channels must be';
            if ($is_bracketed) {
                $buffer .= ' an unbracketed';
            }
            if ($is_comma_separated) {
                $buffer .= $is_bracketed ? ',' : ' a';
                $buffer .= ' space-separated';
            }
            $buffer .= ' list.';
            throw new Sass_Script_Exception($buffer);
        }
        $list = $channels->as_list();
        if (\count($list) >= 2 && $list[0] instanceof Sass_String && !$list[0]->has_quotes() && String_Util::equals_ignore_case($list[0]->get_text(), 'from')) {
            return self::function_string($name, [$original_channels]);
        }
        if (\count($list) > 3) {
            throw new Sass_Script_Exception(sprintf('Only 3 elements allowed, but %s were passed.', \count($list)));
        }
        if (\count($list) < 3) {
            if (Iterable_Util::any($list, fn(Value $value): bool => $value->is_var()) || \count($list) > 0 && self::is_var_slash($list[0])) {
                return self::function_string($name, [$original_channels]);
            }
            $argument = $argument_names[\count($list)];
            throw new Sass_Script_Exception("Missing element {$argument}.");
        }
        if ($alpha_from_slash_list !== null) {
            return [...$list, $alpha_from_slash_list];
        }
        if ($list[2] instanceof Sass_Number && $list[2]->get_as_slash() !== null) {
            [$channel3, $alpha] = $list[2]->get_as_slash();
            return [$list[0], $list[1], $channel3, $alpha];
        }
        if ($list[2] instanceof Sass_String && !$list[2]->has_quotes() && str_contains($list[2]->get_text(), '/')) {
            return self::function_string($name, [$channels]);
        }
        return $list;
    }
    /**
     * Returns whether $value is an unquoted string that start with `var(` and
     * contains `/`.
     */
    private static function is_var_slash(Value $value): bool
    {
        return $value instanceof Sass_String && $value->has_quotes() && String_Util::starts_with_ignore_case($value->get_text(), 'var(') && str_contains($value->get_text(), '/');
    }
    /**
     * Asserts that $number is a percentage or has no units, and normalizes the
     * value.
     *
     * If $number has no units, its value is clamped to be greater than `0` or
     * less than $max and returned. If $number is a percentage, it's scaled to be
     * within `0` and $max. Otherwise, this throws a {@see SassScriptException}.
     *
     * $name is used to identify the argument in the error message.
     */
    private static function percentage_or_unitless(Sass_Number $number, float $max, string $name): float
    {
        if (!$number->has_units()) {
            $value = $number->get_value();
        } elseif ($number->has_unit('%')) {
            $value = $max * $number->get_value() / 100;
        } else {
            throw new Sass_Script_Exception("\${$name}: Expected {$number} to have unit \"%\" or no units.");
        }
        return Number_Util::clamp($value, 0, $max);
    }
    private static function mix_colors(Sass_Color $color1, Sass_Color $color2, Sass_Number $weight): Sass_Color
    {
        self::check_percent($weight, 'weight');
        // This algorithm factors in both the user-provided weight (w) and the
        // difference between the alpha values of the two colors (a) to decide how
        // to perform the weighted average of the two RGB values.
        //
        // It works by first normalizing both parameters to be within [-1, 1], where
        // 1 indicates "only use color1", -1 indicates "only use color2", and all
        // values in between indicated a proportionately weighted average.
        //
        // Once we have the normalized variables w and a, we apply the formula
        // (w + a)/(1 + w*a) to get the combined weight (in [-1, 1]) of color1. This
        // formula has two especially nice properties:
        //
        //   * When either w or a are -1 or 1, the combined weight is also that
        //     number (cases where w * a == -1 are undefined, and handled as a
        //     special case).
        //
        //   * When a is 0, the combined weight is w, and vice versa.
        //
        // Finally, the weight of color1 is renormalized to be within [0, 1] and the
        // weight of color2 is given by 1 minus the weight of color1.
        $weight_scale = $weight->value_in_range(0, 100, 'weight') / 100;
        $normalized_weight = $weight_scale * 2 - 1;
        $alpha_distance = $color1->get_alpha() - $color2->get_alpha();
        $combined_weight1 = $normalized_weight * $alpha_distance == -1 ? $normalized_weight : ($normalized_weight + $alpha_distance) / (1 + $normalized_weight * $alpha_distance);
        $weight1 = ($combined_weight1 + 1) / 2;
        $weight2 = 1 - $weight1;
        return Sass_Color::rgb(Number_Util::fuzzy_round($color1->get_red() * $weight1 + $color2->get_red() * $weight2), Number_Util::fuzzy_round($color1->get_green() * $weight1 + $color2->get_green() * $weight2), Number_Util::fuzzy_round($color1->get_blue() * $weight1 + $color2->get_blue() * $weight2), $color1->get_alpha() * $weight_scale + $color2->get_alpha() * (1 - $weight_scale));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function opacify(array $arguments): Sass_Color
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_alpha(Number_Util::clamp($color->get_alpha() + $amount->value_in_range_with_unit(0, 1, 'amount', ''), 0, 1));
    }
    /**
     * @param list<Value> $arguments
     */
    public static function transparentize(array $arguments): Sass_Color
    {
        $color = $arguments[0]->assert_color('color');
        $amount = $arguments[1]->assert_number('amount');
        return $color->change_alpha(Number_Util::clamp($color->get_alpha() - $amount->value_in_range_with_unit(0, 1, 'amount', ''), 0, 1));
    }
}