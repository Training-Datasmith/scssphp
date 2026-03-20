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
namespace Scss_Php\Scss_Php\Visitor;

use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Calculation;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Function;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Mixin;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
/**
 * An interface for visitors that traverse SassScript $values.
 *
 * @internal
 *
 * @template T
 */
interface Value_Visitor
{
    /**
     * @return T
     */
    public function visit_boolean(Sass_Boolean $value);
    /**
     * @return T
     */
    public function visit_calculation(Sass_Calculation $value);
    /**
     * @return T
     */
    public function visit_color(Sass_Color $value);
    /**
     * @return T
     */
    public function visit_function(Sass_Function $value);
    /**
     * @return T
     */
    public function visit_mixin(Sass_Mixin $value);
    /**
     * @return T
     */
    public function visit_list(Sass_List $value);
    /**
     * @return T
     */
    public function visit_map(Sass_Map $value);
    /**
     * @return T
     */
    public function visit_null();
    /**
     * @return T
     */
    public function visit_number(Sass_Number $value);
    /**
     * @return T
     */
    public function visit_string(Sass_String $value);
}