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

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Util\Path;
/**
 * A single stack frame. Each frame points to a precise location in Sass code.
 */
final class Frame
{
    /**
     * The URI of the file in which the code is located.
     */
    private readonly Uri_Interface $url;
    public function __construct(
        Uri_Interface $url,
        /**
         * The line number on which the code location is located.
         *
         * This can be null, indicating that the line number is unknown or
         * unimportant.
         */
        private readonly ?int $line,
        /**
         * The column number of the code location.
         *
         * This can be null, indicating that the column number is unknown or
         * unimportant.
         */
        private readonly ?int $column,
        /**
         * The name of the member in which the code location occurs.
         */
        private readonly ?string $member
    )
    {
        $this->url = $url;
    }
    /**
     * The URI of the file in which the code is located.
     */
    public function get_url(): Uri_Interface
    {
        return $this->url;
    }
    /**
     * The line number on which the code location is located.
     *
     * This can be null, indicating that the line number is unknown or
     * unimportant.
     */
    public function get_line(): ?int
    {
        return $this->line;
    }
    /**
     * The column number of the code location.
     *
     * This can be null, indicating that the column number is unknown or
     * unimportant.
     */
    public function get_column(): ?int
    {
        return $this->column;
    }
    /**
     * The name of the member in which the code location occurs.
     */
    public function get_member(): ?string
    {
        return $this->member;
    }
    /**
     * A human-friendly description of the code location.
     */
    public function get_location(): string
    {
        $library = Path::pretty_uri($this->url);
        if ($this->line === null) {
            return $library;
        }
        if ($this->column === null) {
            return $library . ' ' . $this->line;
        }
        return $library . ' ' . $this->line . ':' . $this->column;
    }
}