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

use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Source_Span\File_Span;
/**
 * An argument declared as part of an {@see ArgumentDeclaration}.
 *
 * @internal
 */
final class Argument implements Sass_Node, Sass_Declaration
{
    private readonly File_Span $span;
    public function __construct(private readonly string $name, File_Span $span, private readonly ?Expression $default_value = null)
    {
        $this->span = $span;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * The variable name as written in the document, without underscores
     * converted to hyphens and including the leading `$`.
     *
     * This isn't particularly efficient, and should only be used for error
     * messages.
     */
    public function get_original_name(): string
    {
        if ($this->default_value === null) {
            return $this->span->get_text();
        }
        return Util::declaration_name($this->span);
    }
    public function get_name_span(): File_Span
    {
        if ($this->default_value === null) {
            return $this->span;
        }
        return Span_Util::initial_identifier($this->span, 1);
    }
    public function get_default_value(): ?Expression
    {
        return $this->default_value;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function __toString(): string
    {
        if ($this->default_value === null) {
            return $this->name;
        }
        return $this->name . ': ' . $this->default_value;
    }
}