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
namespace Scss_Php\Scss_Php\Evaluation;

use Scss_Php\Scss_Php\Ast\Css\Css_Stylesheet;
/**
 * The result of compiling a Sass document to a CSS tree, along with metadata
 * about the compilation process.
 *
 * @internal
 */
final class Evaluate_Result
{
    /**
     * @param list<string> $loadedUrls
     */
    public function __construct(private readonly Css_Stylesheet $stylesheet, private readonly array $loaded_urls)
    {
    }
    public function get_stylesheet(): Css_Stylesheet
    {
        return $this->stylesheet;
    }
    /**
     * @return list<string>
     */
    public function get_loaded_urls(): array
    {
        return $this->loaded_urls;
    }
}