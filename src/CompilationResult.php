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

namespace ScssPhp\ScssPhp;

final class CompilationResult
{
    /**
     * @param list<string> $includedFiles
     */
    public function __construct(private readonly string $css, private readonly ?string $sourceMap, private readonly array $includedFiles)
    {
    }

    public function getCss(): string
    {
        return $this->css;
    }

    /**
     * @return list<string>
     */
    public function getIncludedFiles(): array
    {
        return $this->includedFiles;
    }

    /**
     * The sourceMap content, if it was generated
     */
    public function getSourceMap(): ?string
    {
        return $this->sourceMap;
    }
}
