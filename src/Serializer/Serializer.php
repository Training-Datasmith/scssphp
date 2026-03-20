<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Serializer;

use Scss_Php\Scss_Php\Ast\Css\Css_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Parent_Node;
use Scss_Php\Scss_Php\Ast\Selector\Selector;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Output_Style;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Visitor\Css_Visitor;
/**
 * @internal
 */
final class Serializer
{
    public static function serialize(Css_Node $node, bool $inspect = false, Output_Style $style = Output_Style::EXPANDED, bool $source_map = false, bool $charset = true, ?Logger_Interface $logger = null): Serialize_Result
    {
        $visitor = new Serialize_Visitor($inspect, true, $style, $source_map, $logger);
        $node->accept($visitor);
        $css = (string) $visitor->get_buffer();
        $prefix = '';
        if ($charset && strlen($css) !== mb_strlen($css, 'UTF-8')) {
            if ($style === Output_Style::COMPRESSED) {
                $prefix = "﻿";
            } else {
                $prefix = '@charset "UTF-8";' . "\n";
            }
        }
        return new Serialize_Result($prefix . $css, $source_map ? $visitor->get_buffer()->build_source_map($prefix) : null);
    }
    /**
     * Converts $value to a CSS string.
     *
     * If $inspect is `true`, this will emit an unambiguous representation of the
     * source structure. Note however that, although this will be valid SCSS, it
     * may not be valid CSS. If $inspect is `false` and $value can't be
     * represented in plain CSS, throws a {@see SassScriptException}.
     *
     * If $quote is `false`, quoted strings are emitted without quotes.
     */
    public static function serialize_value(Value $value, bool $inspect = false, bool $quote = true): string
    {
        // Force loading the CssParentNode and CssVisitor before using the visitor because of a weird PHP behavior.
        class_exists(Css_Parent_Node::class);
        class_exists(Css_Visitor::class);
        $visitor = new Serialize_Visitor($inspect, $quote);
        $value->accept($visitor);
        return (string) $visitor->get_buffer();
    }
    /**
     * Converts $selector to a CSS string.
     *
     * If $inspect is `true`, this will emit an unambiguous representation of the
     * source structure. Note however that, although this will be valid SCSS, it
     * may not be valid CSS. If $inspect is `false` and $selector can't be
     * represented in plain CSS, throws a {@see SassScriptException}.
     */
    public static function serialize_selector(Selector $selector, bool $inspect = false): string
    {
        // Force loading the CssParentNode and CssVisitor before using the visitor because of a weird PHP behavior.
        class_exists(Css_Parent_Node::class);
        class_exists(Css_Visitor::class);
        $visitor = new Serialize_Visitor($inspect);
        $selector->accept($visitor);
        return (string) $visitor->get_buffer();
    }
}