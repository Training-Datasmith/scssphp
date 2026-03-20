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
 * An importer that never imports any stylesheets.
 *
 * This is used for stylesheets which don't support relative imports, such as
 * those created from PHP code with plain strings.
 */
final class No_Op_Importer extends Importer
{
    public function canonicalize(Uri_Interface $url): ?Uri_Interface
    {
        return null;
    }
    public function load(Uri_Interface $url): ?Importer_Result
    {
        return null;
    }
    public function could_canonicalize(Uri_Interface $url, Uri_Interface $canonical_url): bool
    {
        return false;
    }
    public function __toString(): string
    {
        return '(unknown)';
    }
}