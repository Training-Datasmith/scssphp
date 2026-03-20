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
namespace Scss_Php\Scss_Php\Ast\Sass;

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Ast\Css\Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Parent_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Supports_Rule;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\At_Root_Query_Parser;
use Scss_Php\Scss_Php\Parser\Interpolation_Map;
/**
 * A query for the `@at-root` rule.
 *
 * @internal
 */
final class At_Root_Query
{
    /**
     * Parses an at-root query from $contents.
     *
     * If passed, $url is the name of the file from which $contents comes.
     *
     * @throws SassFormatException if parsing fails
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null, ?Interpolation_Map $interpolation_map = null): At_Root_Query
    {
        return (new At_Root_Query_Parser($contents, $logger, $url, $interpolation_map))->parse();
    }
    /**
     * @param string[] $names
     */
    public static function create(array $names, bool $include): At_Root_Query
    {
        return new At_Root_Query($names, $include, \in_array('all', $names, true), \in_array('rule', $names, true));
    }
    /**
     * The default at-root query
     */
    public static function get_default(): At_Root_Query
    {
        return new At_Root_Query([], false, false, true);
    }
    /**
     * @param string[] $names
     */
    private function __construct(
        /**
         * The names of the rules included or excluded by this query.
         *
         * There are two special names. "all" indicates that all rules are included
         * or excluded, and "rule" indicates style rules are included or excluded.
         */
        private readonly array $names,
        /**
         * Whether the query includes or excludes rules with the specified names.
         */
        private readonly bool $include,
        /**
         * Whether this includes or excludes *all* rules.
         */
        private readonly bool $all,
        /**
         * Whether this includes or excludes style rules.
         */
        private readonly bool $rule
    )
    {
    }
    public function get_include(): bool
    {
        return $this->include;
    }
    /**
     * @return string[]
     */
    public function get_names(): array
    {
        return $this->names;
    }
    /**
     * Whether this excludes style rules.
     *
     * Note that this takes {@see include} into account.
     */
    public function excludes_style_rules(): bool
    {
        return ($this->all || $this->rule) !== $this->include;
    }
    /**
     * Returns whether $this excludes $node
     */
    public function excludes(Css_Parent_Node $node): bool
    {
        if ($this->all) {
            return !$this->include;
        }
        if ($node instanceof Css_Style_Rule) {
            return $this->excludes_style_rules();
        }
        if ($node instanceof Css_Media_Rule) {
            return $this->excludes_name('media');
        }
        if ($node instanceof Css_Supports_Rule) {
            return $this->excludes_name('supports');
        }
        if ($node instanceof Css_At_Rule) {
            return $this->excludes_name(strtolower($node->get_name()->get_value()));
        }
        return false;
    }
    /**
     * Returns whether $this excludes an at-rule with the given $name.
     */
    public function excludes_name(string $name): bool
    {
        return ($this->all || \in_array($name, $this->names, true)) !== $this->include;
    }
}