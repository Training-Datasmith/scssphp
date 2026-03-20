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
/**
 * A {@see Statement} that can have child statements.
 *
 * This has a generic parameter so that its subclasses can choose whether or
 * not their children lists are nullable.
 *
 * @template T
 * @psalm-template T of (Statement[]|null)
 *
 * @internal
 */
abstract class Parent_Statement implements Statement
{
    private readonly bool $declarations;
    /**
     * @param T $children
     */
    public function __construct(private readonly ?array $children)
    {
        if ($this->children === null) {
            $this->declarations = false;
            return;
        }
        foreach ($this->children as $child) {
            if ($child instanceof Variable_Declaration || $child instanceof Function_Rule || $child instanceof Mixin_Rule) {
                $this->declarations = true;
                return;
            }
            if ($child instanceof Import_Rule) {
                foreach ($child->get_imports() as $import) {
                    if ($import instanceof Dynamic_Import) {
                        $this->declarations = true;
                        return;
                    }
                }
            }
        }
        $this->declarations = false;
    }
    /**
     * @return T
     */
    final public function get_children(): ?array
    {
        return $this->children;
    }
    final public function has_declarations(): bool
    {
        return $this->declarations;
    }
}