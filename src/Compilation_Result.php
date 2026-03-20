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
namespace Scss_Php\Scss_Php;

final class Compilation_Result
{
    /**
     * @param list<string> $includedFiles
     */
    public function __construct(private readonly string $css, private readonly ?string $source_map, private readonly array $included_files)
    {
    }
    public function get_css(): string
    {
        return $this->css;
    }
    /**
     * @return list<string>
     */
    public function get_included_files(): array
    {
        return $this->included_files;
    }
    /**
     * The sourceMap content, if it was generated
     */
    public function get_source_map(): ?string
    {
        return $this->source_map;
    }
}