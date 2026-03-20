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
namespace Scss_Php\Scss_Php\Ast\Css;

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Interpolation_Map;
use Scss_Php\Scss_Php\Parser\Media_Query_Parser;
use Scss_Php\Scss_Php\Util\Equatable;
/**
 * A plain CSS media query, as used in `@media` and `@import`.
 *
 * @internal
 */
final class Css_Media_Query implements Media_Query_Merge_Result, Equatable
{
    /**
     * Parses a media query from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes.
     *
     * @return list<CssMediaQuery>
     *
     * @throws SassFormatException if parsing fails
     */
    public static function parse_list(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null, ?Interpolation_Map $interpolation_map = null): array
    {
        return (new Media_Query_Parser($contents, $logger, $url, $interpolation_map))->parse();
    }
    /**
     * @param list<string> $conditions
     */
    private function __construct(
        /**
         * Media conditions, including parentheses.
         *
         * This is anything that can appear in the [`<media-in-parens>`] production.
         *
         * [`<media-in-parens>`]: https://drafts.csswg.org/mediaqueries-4/#typedef-media-in-parens
         */
        private readonly array $conditions = [],
        /**
         * Whether {@see $conditions} is a conjunction or a disjunction.
         *
         * In other words, if this is `true` this query matches when _all_
         * {@see $conditions} are met, and if it's `false` this query matches when _any_
         * condition in {@see $conditions} is met.
         *
         * If this is `false`, {@see $modifier} and {@see $type} will both be `null`.
         */
        private readonly bool $conjunction = true,
        /**
         * The media type, for example "screen" or "print".
         *
         * This may be `null`. If so, {@see $conditions} will not be empty.
         */
        private readonly ?string $type = null,
        /**
         * The modifier, probably either "not" or "only".
         *
         * This may be `null` if no modifier is in use.
         */
        private readonly ?string $modifier = null
    )
    {
    }
    /**
     * Creates a media query specifies a type and, optionally, conditions.
     *
     * This always sets {@see $conjunction} to `true`.
     *
     * @param list<string> $conditions
     */
    public static function type(?string $type, ?string $modifier = null, array $conditions = []): Css_Media_Query
    {
        return new Css_Media_Query($conditions, true, $type, $modifier);
    }
    /**
     * Creates a media query that matches $conditions according to
     * $conjunction.
     *
     * The $conjunction argument may not be null if $conditions is longer than
     * a single element.
     *
     * @param list<string> $conditions
     */
    public static function condition(array $conditions, ?bool $conjunction = null): Css_Media_Query
    {
        if (\count($conditions) > 1 && $conjunction === null) {
            throw new \InvalidArgumentException('If conditions is longer than one element, conjunction may not be null.');
        }
        return new Css_Media_Query($conditions, $conjunction ?? true);
    }
    public function get_modifier(): ?string
    {
        return $this->modifier;
    }
    public function get_type(): ?string
    {
        return $this->type;
    }
    public function is_conjunction(): bool
    {
        return $this->conjunction;
    }
    /**
     * @return list<string>
     */
    public function get_conditions(): array
    {
        return $this->conditions;
    }
    /**
     * Whether this media query matches all media types.
     */
    public function matches_all_types(): bool
    {
        return $this->type === null || strtolower($this->type) === 'all';
    }
    /**
     * Merges this with $other to return a query that matches the intersection
     * of both inputs.
     */
    public function merge(Css_Media_Query $other): Media_Query_Merge_Result
    {
        if (!$this->conjunction || !$other->conjunction) {
            return Media_Query_Singleton_Merge_Result::unrepresentable;
        }
        $our_modifier = $this->modifier !== null ? strtolower($this->modifier) : null;
        $our_type = $this->type !== null ? strtolower($this->type) : null;
        $their_modifier = $other->modifier !== null ? strtolower($other->modifier) : null;
        $their_type = $other->type !== null ? strtolower($other->type) : null;
        if ($our_type === null && $their_type === null) {
            return self::condition(array_merge($this->conditions, $other->conditions), true);
        }
        if (($our_modifier === 'not') !== ($their_modifier === 'not')) {
            if ($our_type === $their_type) {
                $negative_conditions = $our_modifier === 'not' ? $this->conditions : $other->conditions;
                $positive_conditions = $our_modifier === 'not' ? $other->conditions : $this->conditions;
                // If the negative conditions are a subset of the positive conditions, the
                // query is empty. For example, `not screen and (color)` has no
                // intersection with `screen and (color) and (grid)`.
                //
                // However, `not screen and (color)` *does* intersect with `screen and
                // (grid)`, because it means `not (screen and (color))` and so it allows
                // a screen with no color but with a grid.
                if (empty(array_diff($negative_conditions, $positive_conditions))) {
                    return Media_Query_Singleton_Merge_Result::empty;
                }
                return Media_Query_Singleton_Merge_Result::unrepresentable;
            }
            if ($this->matches_all_types() || $other->matches_all_types()) {
                return Media_Query_Singleton_Merge_Result::unrepresentable;
            }
            if ($our_modifier === 'not') {
                $modifier = $their_modifier;
                $type = $their_type;
                $conditions = $other->conditions;
            } else {
                $modifier = $our_modifier;
                $type = $our_type;
                $conditions = $this->conditions;
            }
        } elseif ($our_modifier === 'not') {
            // CSS has no way of representing "neither screen nor print".
            if ($our_type !== $their_type) {
                return Media_Query_Singleton_Merge_Result::unrepresentable;
            }
            $more_conditions = \count($this->conditions) > \count($other->conditions) ? $this->conditions : $other->conditions;
            $fewer_conditions = \count($this->conditions) > \count($other->conditions) ? $other->conditions : $this->conditions;
            // If one set of features is a superset of the other, use those features
            // because they're strictly narrower.
            if (empty(array_diff($fewer_conditions, $more_conditions))) {
                $modifier = $our_modifier;
                // "not"
                $type = $our_type;
                $conditions = $more_conditions;
            } else {
                // Otherwise, there's no way to represent the intersection.
                return Media_Query_Singleton_Merge_Result::unrepresentable;
            }
        } elseif ($this->matches_all_types()) {
            $modifier = $their_modifier;
            // Omit the type if either input query did, since that indicates that they
            // aren't targeting a browser that requires "all and".
            $type = $other->matches_all_types() && $our_type === null ? null : $their_type;
            $conditions = array_merge($this->conditions, $other->conditions);
        } elseif ($other->matches_all_types()) {
            $modifier = $our_modifier;
            $type = $our_type;
            $conditions = array_merge($this->conditions, $other->conditions);
        } elseif ($our_type !== $their_type) {
            return Media_Query_Singleton_Merge_Result::empty;
        } else {
            $modifier = $our_modifier ?? $their_modifier;
            $type = $our_type;
            $conditions = array_merge($this->conditions, $other->conditions);
        }
        return Css_Media_Query::type($type === $our_type ? $this->type : $other->type, $modifier === $our_modifier ? $this->modifier : $other->modifier, $conditions);
    }
    public function equals(object $other): bool
    {
        return $other instanceof Css_Media_Query && $other->modifier === $this->modifier && $other->type === $this->type && $other->conditions === $this->conditions;
    }
}