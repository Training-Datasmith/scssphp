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
/**
 * @internal
 */
final class Canonicalize_Result
{
    public function __construct(public readonly Importer $importer, public readonly Uri_Interface $canonical_url, public readonly Uri_Interface $original_url)
    {
    }
}