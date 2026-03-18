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

namespace ScssPhp\ScssPhp\Ast\Sass\Statement;

use ScssPhp\ScssPhp\Ast\Sass\Import\DynamicImport;
use ScssPhp\ScssPhp\Ast\Sass\Statement;
use ScssPhp\ScssPhp\Util\IterableUtil;

/**
 * The superclass of `@if` and `@else` clauses.
 *
 * @internal
 */
abstract class IfRuleClause
{
    private readonly bool $declarations;

    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly array $children)
    {
        $this->declarations = IterableUtil::any($this->children, function (Statement $child): bool {
            if ($child instanceof VariableDeclaration || $child instanceof FunctionRule || $child instanceof MixinRule) {
                return true;
            }

            if ($child instanceof ImportRule) {
                return IterableUtil::any($child->getImports(), fn ($import): bool => $import instanceof DynamicImport);
            }

            return false;
        });
    }

    /**
     * @return Statement[]
     */
    final public function getChildren(): array
    {
        return $this->children;
    }

    final public function hasDeclarations(): bool
    {
        return $this->declarations;
    }
}
