<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2018-2020 Anthon Pang
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Serializer;

/**
 * @internal
 */
interface String_Buffer extends \Stringable
{
    /**
     * Returns the length of the content that has been accumulated so far.
     */
    public function get_length(): int;
    public function write(string $string): void;
    /**
     * Writes a single char to the buffer.
     */
    public function write_char(string $char): void;
}