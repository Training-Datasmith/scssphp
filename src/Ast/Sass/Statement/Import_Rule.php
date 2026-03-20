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
namespace Scss_Php\Scss_Php\Ast\Sass\Statement;

use Scss_Php\Scss_Php\Ast\Sass\Import;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * An `@import` rule.
 *
 * @internal
 */
final class Import_Rule implements Statement
{
    private readonly File_Span $span;
    /**
     * @param list<Import> $imports
     */
    public function __construct(private readonly array $imports, File_Span $span)
    {
        $this->span = $span;
    }
    /**
     * @return list<Import>
     */
    public function get_imports(): array
    {
        return $this->imports;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_import_rule($this);
    }
    public function __toString(): string
    {
        return '@import ' . implode(', ', $this->imports) . ';';
    }
}