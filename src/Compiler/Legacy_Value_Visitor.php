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
namespace Scss_Php\Scss_Php\Compiler;

use Scss_Php\Scss_Php\Compiler;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Node\Number;
use Scss_Php\Scss_Php\Type;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Value\Sass_Argument_List;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Calculation;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Function;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Mixin;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * Converts values to the legacy representation.
 *
 * @internal
 * @template-implements ValueVisitor<array|Number>
 */
final class Legacy_Value_Visitor implements Value_Visitor
{
    public function visit_boolean(Sass_Boolean $value)
    {
        return $value->get_value() ? Compiler::$true : Compiler::$false;
    }
    public function visit_calculation(Sass_Calculation $value): array
    {
        return [Type::T_STRING, '', $value->to_css_string()];
    }
    public function visit_color(Sass_Color $value): array
    {
        if (Number_Util::fuzzy_equals($value->get_alpha(), 1)) {
            return [Type::T_COLOR, $value->get_red(), $value->get_green(), $value->get_blue()];
        }
        return [Type::T_COLOR, $value->get_red(), $value->get_green(), $value->get_blue(), $value->get_alpha()];
    }
    public function visit_function(Sass_Function $value): never
    {
        throw new Sass_Script_Exception('Functions are not supported by the legacy value API. Migrate your custom function to the new API to accept mixins as arguments.');
    }
    public function visit_mixin(Sass_Mixin $value): never
    {
        throw new Sass_Script_Exception('Mixins are not supported by the legacy value API. Migrate your custom function to the new API to accept mixins as arguments.');
    }
    public function visit_list(Sass_List $value): array
    {
        $items = [];
        foreach ($value->as_list() as $item) {
            $items[] = $item->accept($this);
        }
        $list = [Type::T_LIST, $value->get_separator()->get_separator() ?? '', $items];
        if ($value->has_brackets()) {
            $list['enclosing'] = 'bracket';
        }
        if ($value instanceof Sass_Argument_List) {
            $keywords = [];
            foreach ($value->get_keywords() as $keyword_name => $keyword_value) {
                $keywords[$keyword_name] = $keyword_value->accept($this);
            }
            $list[3] = $keywords;
        }
        return $list;
    }
    public function visit_map(Sass_Map $value): array
    {
        $keys = [];
        $values = [];
        foreach ($value->get_contents() as $key => $item) {
            $keys[] = $key->accept($this);
            $values[] = $item->accept($this);
        }
        return [Type::T_MAP, $keys, $values];
    }
    public function visit_null()
    {
        return Compiler::$null;
    }
    public function visit_number(Sass_Number $value): \Scss_Php\Scss_Php\Node\Number
    {
        return new Number($value->get_value(), $value->get_numerator_units(), $value->get_denominator_units());
    }
    public function visit_string(Sass_String $value): array
    {
        return [Type::T_STRING, $value->has_quotes() ? '"' : '', [$value->get_text()]];
    }
}