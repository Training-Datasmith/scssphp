<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Evaluation;

use ScssPhp\ScssPhp\Ast\Css\CssStylesheet;

/**
 * The result of compiling a Sass document to a CSS tree, along with metadata
 * about the compilation process.
 *
 * @internal
 */
final class EvaluateResult
{
    /**
     * @param list<string> $loadedUrls
     */
    public function __construct(private readonly CssStylesheet $stylesheet, private readonly array $loadedUrls)
    {
    }

    public function getStylesheet(): CssStylesheet
    {
        return $this->stylesheet;
    }

    /**
     * @return list<string>
     */
    public function getLoadedUrls(): array
    {
        return $this->loadedUrls;
    }
}
