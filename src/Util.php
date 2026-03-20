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

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use Scss_Php\Scss_Php\Stack_Trace\Frame;
use Scss_Php\Scss_Php\Util\String_Util;
use Source_Span\File_Span;
/**
 * Utility functions
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
final class Util
{
    /**
     * Returns $string with every line indented $indentation spaces.
     */
    public static function indent(string $string, int $indentation): string
    {
        return implode("\n", array_map(fn($line) => str_repeat(' ', $indentation) . $line, explode("\n", $string)));
    }
    /**
     * Encode URI component
     */
    public static function encode_uri_component(string $string): string
    {
        $revert = ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')'];
        return strtr(rawurlencode($string), $revert);
    }
    public static function frame_for_span(File_Span $span, string $member, ?Uri_Interface $url = null): Frame
    {
        return new Frame($url ?? $span->get_source_url() ?? Uri::new('-'), $span->get_start()->get_line() + 1, $span->get_start()->get_column() + 1, $member);
    }
    /**
     * Returns the variable name (including the leading `$`) from a $span that
     * covers a variable declaration, which includes the variable name as well as
     * the colon and expression following it.
     *
     * This isn't particularly efficient, and should only be used for error
     * messages.
     */
    public static function declaration_name(File_Span $span): string
    {
        $text = $span->get_text();
        $pos = strpos($text, ':');
        return String_Util::trim_ascii_right(substr($text, 0, $pos === false ? null : $pos));
    }
    /**
     * Returns $name without a vendor prefix.
     *
     * If $name has no vendor prefix, it's returned as-is.
     */
    public static function unvendor(string $name): string
    {
        $length = \strlen($name);
        if ($length < 2) {
            return $name;
        }
        if ($name[0] !== '-') {
            return $name;
        }
        if ($name[1] === '-') {
            return $name;
        }
        for ($i = 2; $i < $length; $i++) {
            if ($name[$i] === '-') {
                return substr($name, $i + 1);
            }
        }
        return $name;
    }
    /**
     * Like {@see \SplObjectStorage::addAll()}, but for two-layer maps.
     *
     * This avoids copying inner maps from $source if possible.
     *
     * @template K1 of object
     * @template K2 of object
     * @template V
     * @template Inner of \SplObjectStorage<K2, V>
     *
     * @param \SplObjectStorage<K1, Inner> $destination
     * @param \SplObjectStorage<K1, Inner> $source
     */
    public static function map_add_all2(\Spl_Object_Storage $destination, \Spl_Object_Storage $source): void
    {
        foreach ($source as $key) {
            $inner = $source->get_info();
            $inner_destination = $destination[$key] ?? null;
            if ($inner_destination !== null) {
                $inner_destination->add_all($inner);
            } else {
                $destination[$key] = $inner;
            }
        }
    }
}