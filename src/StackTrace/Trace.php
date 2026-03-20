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
namespace Scss_Php\Scss_Php\Stack_Trace;

/**
 * A stack trace, comprised of a list of stack frames.
 */
final class Trace
{
    /**
     * @param list<Frame> $frames
     */
    public function __construct(
        /**
         * @readonly
         */
        private readonly array $frames
    )
    {
    }
    /**
     * @return list<Frame>
     */
    public function get_frames(): array
    {
        return $this->frames;
    }
    public function get_formatted_trace(): string
    {
        $longest = 0;
        foreach ($this->frames as $frame) {
            $length = \strlen($frame->get_location());
            $longest = max($longest, $length);
        }
        return implode('', array_map(fn(Frame $frame): string => str_pad($frame->get_location(), $longest) . '  ' . $frame->get_member() . "\n", $this->frames));
    }
}