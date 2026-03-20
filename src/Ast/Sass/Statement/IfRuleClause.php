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

use Scss_Php\Scss_Php\Ast\Sass\Import\Dynamic_Import;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Util\Iterable_Util;
/**
 * The superclass of `@if` and `@else` clauses.
 *
 * @internal
 */
abstract class If_Rule_Clause
{
    private readonly bool $declarations;
    /**
     * @param Statement[] $children
     */
    public function __construct(private readonly array $children)
    {
        $this->declarations = Iterable_Util::any($this->children, function (Statement $child): bool {
            if ($child instanceof Variable_Declaration || $child instanceof Function_Rule || $child instanceof Mixin_Rule) {
                return true;
            }
            if ($child instanceof Import_Rule) {
                return Iterable_Util::any($child->get_imports(), fn($import): bool => $import instanceof Dynamic_Import);
            }
            return false;
        });
    }
    /**
     * @return Statement[]
     */
    final public function get_children(): array
    {
        return $this->children;
    }
    final public function has_declarations(): bool
    {
        return $this->declarations;
    }
}