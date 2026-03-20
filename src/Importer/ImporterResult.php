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
namespace Scss_Php\Scss_Php\Importer;

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use Scss_Php\Scss_Php\Syntax;
final class Importer_Result
{
    public function __construct(private readonly string $contents, private readonly Syntax $syntax, private readonly ?Uri_Interface $source_map_url = null)
    {
    }
    public function get_contents(): string
    {
        return $this->contents;
    }
    /**
     * An absolute, browser-accessible URL indicating the resolved location of
     * the imported stylesheet.
     *
     * This should be a `file:` URL if one is available, but an `http:` URL is
     * acceptable as well. If no URL is supplied, a `data:` URL is generated
     * automatically from {@see contents}.
     */
    public function get_source_map_url(): Uri_Interface
    {
        return $this->source_map_url ?? Uri::from_data($this->contents, '', 'charset=utf-8');
    }
    /**
     * The syntax to use to parse the stylesheet.
     */
    public function get_syntax(): Syntax
    {
        return $this->syntax;
    }
}