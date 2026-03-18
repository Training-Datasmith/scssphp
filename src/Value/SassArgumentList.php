<?php

declare(strict_types=1);

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Value;

/**
 * A SassScript argument list.
 *
 * An argument list comes from a rest argument. It's distinct from a normal
 * {@see SassList} in that it may contain a keyword map as well as the positional
 * arguments.
 */
final class SassArgumentList extends SassList
{
    private bool $keywordAccessed = false;

    /**
     * SassArgumentList constructor.
     *
     * @param list<Value> $contents
     * @param array<string, Value> $keywords
     */
    public function __construct(array $contents, private readonly array $keywords, ListSeparator $separator)
    {
        parent::__construct($contents, $separator);
    }

    /**
     * @return array<string, Value>
     */
    public function getKeywords(): array
    {
        $this->keywordAccessed = true;

        return $this->keywords;
    }

    /**
     * @internal
     */
    public function wereKeywordAccessed(): bool
    {
        return $this->keywordAccessed;
    }
}
