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
namespace Scss_Php\Scss_Php\Util;

/**
 * @internal
 */
final class Equatable_Util
{
    /**
     * @param iterable<mixed> $list
     */
    public static function iterable_contains(iterable $list, Equatable $item): bool
    {
        foreach ($list as $list_item) {
            if (!\is_object($list_item)) {
                continue;
            }
            if ($item === $list_item) {
                return true;
            }
            if ($item->equals($list_item)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Checks whether 2 values are equals, using the Equatable semantic to compare objects if possible.
     *
     * When compared values don't implement {@see Equatable}, they are compared
     * using `===`.
     * Values implementing {@see Equatable} are still compared with `===` first to
     * optimize comparisons to the same object, as an object is always expected to
     * be equal to itself.
     */
    public static function equals(mixed $item1, mixed $item2): bool
    {
        if ($item1 === $item2) {
            return true;
        }
        if ($item1 instanceof Equatable && $item2 instanceof Equatable) {
            return $item1->equals($item2);
        }
        return false;
    }
    /**
     * Checks whether 2 lists are equals, using the Equatable semantic to compare objects if possible.
     *
     * @param list<mixed> $list1
     * @param list<mixed> $list2
     */
    public static function list_equals(array $list1, array $list2): bool
    {
        if (\count($list1) !== \count($list2)) {
            return false;
        }
        foreach ($list1 as $i => $item1) {
            $item2 = $list2[$i];
            if (self::equals($item1, $item2)) {
                continue;
            }
            return false;
        }
        return true;
    }
}