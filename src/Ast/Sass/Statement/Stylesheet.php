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

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Css_Parser;
use Scss_Php\Scss_Php\Parser\Sass_Parser;
use Scss_Php\Scss_Php\Parser\Scss_Parser;
use Scss_Php\Scss_Php\Syntax;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Source_Span\File_Span;
/**
 * A Sass stylesheet.
 *
 * This is the root Sass node. It contains top-level statements.
 *
 * @extends ParentStatement<Statement[]>
 *
 * @internal
 */
final class Stylesheet extends Parent_Statement
{
    private readonly File_Span $span;
    /**
     * @param Statement[] $children
     */
    public function __construct(array $children, File_Span $span, private readonly bool $plain_css = false)
    {
        $this->span = $span;
        parent::__construct($children);
    }
    public function is_plain_css(): bool
    {
        return $this->plain_css;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    public function accept(Statement_Visitor $visitor)
    {
        return $visitor->visit_stylesheet($this);
    }
    /**
     * @throws SassFormatException when parsing fails
     */
    public static function parse(string $contents, Syntax $syntax, ?Logger_Interface $logger = null, ?Uri_Interface $source_url = null): self
    {
        return match ($syntax) {
            Syntax::SASS => self::parse_sass($contents, $logger, $source_url),
            Syntax::SCSS => self::parse_scss($contents, $logger, $source_url),
            Syntax::CSS => self::parse_css($contents, $logger, $source_url),
        };
    }
    /**
     * @throws SassFormatException when parsing fails
     */
    public static function parse_sass(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $source_url = null): self
    {
        return (new Sass_Parser($contents, $logger, $source_url))->parse();
    }
    /**
     * @throws SassFormatException when parsing fails
     */
    public static function parse_scss(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $source_url = null): self
    {
        return (new Scss_Parser($contents, $logger, $source_url))->parse();
    }
    /**
     * @throws SassFormatException when parsing fails
     */
    public static function parse_css(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $source_url = null): self
    {
        return (new Css_Parser($contents, $logger, $source_url))->parse();
    }
    public function __toString(): string
    {
        return implode(' ', $this->get_children());
    }
}