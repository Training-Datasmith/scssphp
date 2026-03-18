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

namespace ScssPhp\ScssPhp\Ast\Css;

use ScssPhp\ScssPhp\Util\EquatableUtil;
use ScssPhp\ScssPhp\Visitor\ModifiableCssVisitor;
use SourceSpan\FileSpan;

/**
 * A modifiable version of {@see CssMediaRule} for use in the evaluation step.
 *
 * @internal
 */
final class ModifiableCssMediaRule extends ModifiableCssParentNode implements CssMediaRule
{
    private readonly FileSpan $span;

    /**
     * @param list<CssMediaQuery> $queries
     */
    public function __construct(private readonly array $queries, FileSpan $span)
    {
        parent::__construct();
        $this->span = $span;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function accept(ModifiableCssVisitor $visitor)
    {
        return $visitor->visitCssMediaRule($this);
    }

    public function equalsIgnoringChildren(ModifiableCssNode $other): bool
    {
        return $other instanceof ModifiableCssMediaRule && EquatableUtil::listEquals($this->queries, $other->queries);
    }

    public function copyWithoutChildren(): ModifiableCssMediaRule
    {
        return new ModifiableCssMediaRule($this->queries, $this->span);
    }
}
