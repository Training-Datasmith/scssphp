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
namespace Scss_Php\Scss_Php\Parser;

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Source_Span\File_Location;
use Source_Span\File_Span;
use Source_Span\Source_Location;
/**
 * A class that can map locations in a string generated from an {@see Interpolation}
 * to the original source code in the interpolation.
 *
 * @internal
 */
final class Interpolation_Map
{
    /**
     * @param list<SourceLocation> $targetLocations
     */
    public function __construct(
        private readonly Interpolation $interpolation,
        /**
         * Locations in the generated string.
         *
         * Each of these indicates the location in the generated string that
         * corresponds to the end of the component at the same index of
         * {@see $interpolation->getContents()}. Its length is always one less than
         * {@see $interpolation->getContents()} because the last element always ends the string.
         */
        private readonly array $target_locations
    )
    {
        $expected_locations = max(0, \count($this->interpolation->get_contents()) - 1);
        if (\count($this->target_locations) !== $expected_locations) {
            $interpolation_parts = \count($this->interpolation->get_contents());
            throw new \InvalidArgumentException("InterpolationMap must have {$expected_locations} targetLocations if the interpolation has {$interpolation_parts} components.");
        }
    }
    public function map_exception(Format_Exception $error): Format_Exception
    {
        if (\count($this->interpolation->get_contents()) === 0) {
            return new Format_Exception($error->get_message(), $this->interpolation->get_span(), $error);
        }
        $target = $error->get_span();
        $source = $this->map_span($target);
        $start_index = $this->index_in_contents($target->get_start());
        $end_index = $this->index_in_contents($target->get_end());
        if (!Iterable_Util::any(array_slice($this->interpolation->get_contents(), $start_index, $end_index - $start_index + 1), fn($content): bool => $content instanceof Expression)) {
            return new Format_Exception($error->get_message(), $source, $error);
        }
        return new Multi_Source_Format_Exception($error->get_message(), $source, '', ['error in interpolated output' => $target], $error);
    }
    public function map_span(File_Span $target): File_Span
    {
        $start = $this->map_location($target->get_start());
        $end = $this->map_location($target->get_end());
        if ($start instanceof File_Span) {
            if ($end instanceof File_Span) {
                return $start->expand($end);
            }
            return $this->interpolation->get_span()->get_file()->span($this->expand_interpolation_span_left($start->get_start()), $end->get_offset());
        }
        if ($end instanceof File_Span) {
            return $this->interpolation->get_span()->get_file()->span($start->get_offset(), $this->expand_interpolation_span_right($end->get_end()));
        }
        return $this->interpolation->get_span()->get_file()->span($start->get_offset(), $end->get_offset());
    }
    /**
     * @return FileSpan|FileLocation
     */
    private function map_location(Source_Location $target): object
    {
        if (\count($this->interpolation->get_contents()) === 0) {
            return $this->interpolation->get_span();
        }
        $index = $this->index_in_contents($target);
        $components = $this->interpolation->get_contents();
        if ($components[$index] instanceof Expression) {
            return $components[$index]->get_span();
        }
        if ($index === 0) {
            $previous_location = $this->interpolation->get_span()->get_start();
        } else {
            $previous_component = $components[$index - 1];
            \assert($previous_component instanceof Expression);
            $previous_location = $this->interpolation->get_span()->get_file()->location($this->expand_interpolation_span_right($previous_component->get_span()->get_end()));
        }
        $offset_in_string = $target->get_offset() - ($index === 0 ? 0 : $this->target_locations[$index - 1]->get_offset());
        return $previous_location->get_file()->location($previous_location->get_offset() + $offset_in_string);
    }
    /**
     * @return int<0, max>
     */
    private function index_in_contents(Source_Location $target): int
    {
        foreach ($this->target_locations as $i => $location) {
            if ($target->get_offset() < $location->get_offset()) {
                return $i;
            }
        }
        \assert(\count($this->interpolation->get_contents()) > 0);
        return \count($this->interpolation->get_contents()) - 1;
    }
    /**
     * Given the start of a {@see FileSpan} covering an interpolated expression, returns
     * the offset of the interpolation's opening `#`.
     *
     * Note that this can be tricked by a `#{` that appears within a single-line
     * comment before the expression, but since it's only used for error
     * reporting that's probably fine.
     */
    private function expand_interpolation_span_left(File_Location $start): int
    {
        $source = $start->get_file()->get_string();
        $i = $start->get_offset() - 1;
        while ($i >= 0) {
            $prev = $source[$i--];
            if ($prev === '{') {
                if ($source[$i] === '#') {
                    break;
                }
            } elseif ($prev === '/') {
                $second = $source[$i--];
                if ($second === '*') {
                    while ($i >= 0) {
                        $char = $source[$i--];
                        if ($char !== '*') {
                            continue;
                        }
                        do {
                            $char = $source[$i--];
                        } while ($char === '*' && $i >= 0);
                        if ($char === '/') {
                            break;
                        }
                    }
                }
            }
        }
        return $i;
    }
    /**
     * Given the end of a {@see FileSpan} covering an interpolated expression, returns
     * the offset of the interpolation's closing `}`.
     */
    private function expand_interpolation_span_right(File_Location $end): int
    {
        $source = $end->get_file()->get_string();
        $i = $end->get_offset();
        while ($i < \strlen((string) $source)) {
            $next = $source[$i++];
            if ($next === '}') {
                break;
            }
            if ($next === '/') {
                $second = $source[$i++];
                if ($second === '/') {
                    while (!Character::is_newline($source[$i++] ?? null)) {
                        // Move forward
                    }
                } elseif ($second === '*') {
                    while (true) {
                        $char = $source[$i++] ?? null;
                        if ($char !== '*') {
                            continue;
                        }
                        do {
                            $char = $source[$i++] ?? null;
                        } while ($char === '*');
                        if ($char === '/') {
                            break;
                        }
                    }
                }
            }
        }
        return $i;
    }
}