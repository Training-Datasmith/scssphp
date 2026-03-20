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
namespace Scss_Php\Scss_Php\Value;

/**
 * A SassScript argument list.
 *
 * An argument list comes from a rest argument. It's distinct from a normal
 * {@see SassList} in that it may contain a keyword map as well as the positional
 * arguments.
 */
final class Sass_Argument_List extends Sass_List
{
    private bool $keyword_accessed = false;
    /**
     * SassArgumentList constructor.
     *
     * @param list<Value> $contents
     * @param array<string, Value> $keywords
     */
    public function __construct(array $contents, private readonly array $keywords, List_Separator $separator)
    {
        parent::__construct($contents, $separator);
    }
    /**
     * @return array<string, Value>
     */
    public function get_keywords(): array
    {
        $this->keyword_accessed = true;
        return $this->keywords;
    }
    /**
     * @internal
     */
    public function were_keyword_accessed(): bool
    {
        return $this->keyword_accessed;
    }
}