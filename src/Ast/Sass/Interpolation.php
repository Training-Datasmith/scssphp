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

use Source_Span\File_Span;
/**
 * Plain text interpolated with Sass expressions.
 *
 * @internal
 */
final class Interpolation implements Sass_Node
{
    /**
     * @var list<string|Expression>
     */
    private readonly array $contents;
    private readonly File_Span $span;
    /**
     * @param list<string|Expression> $contents
     */
    public function __construct(array $contents, File_Span $span)
    {
        for ($i = 0; $i < \count($contents); $i++) {
            // Dart-sass has a validation on the type of elements here. This is useless for us because phpstan supports union types, unlike the Dart type system
            if ($i != 0 && \is_string($contents[$i]) && \is_string($contents[$i - 1])) {
                throw new \InvalidArgumentException('The contents of an Interpolation may not contain adjacent strings.');
            }
        }
        $this->contents = $contents;
        $this->span = $span;
    }
    /**
     * @return list<string|Expression>
     */
    public function get_contents(): array
    {
        return $this->contents;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    /**
     * Returns whether this contains no interpolated expressions.
     */
    public function is_plain(): bool
    {
        return $this->get_as_plain() !== null;
    }
    /**
     * If this contains no interpolated expressions, returns its text contents.
     *
     * Otherwise, returns `null`.
     *
     * @psalm-mutation-free
     */
    public function get_as_plain(): ?string
    {
        if (\count($this->contents) === 0) {
            return '';
        }
        if (\count($this->contents) > 1) {
            return null;
        }
        if (\is_string($this->contents[0])) {
            return $this->contents[0];
        }
        return null;
    }
    /**
     * Returns the plain text before the interpolation, or the empty string.
     */
    public function get_initial_plain(): string
    {
        $first = $this->contents[0] ?? null;
        if (\is_string($first)) {
            return $first;
        }
        return '';
    }
    public function __toString(): string
    {
        return implode('', array_map(fn(\Scss_Php\Scss_Php\Ast\Sass\Expression|string $value): string => \is_string($value) ? $value : '#{' . $value . '}', $this->contents));
    }
}