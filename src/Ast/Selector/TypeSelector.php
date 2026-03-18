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

namespace ScssPhp\ScssPhp\Ast\Selector;

use ScssPhp\ScssPhp\Extend\ExtendUtil;
use ScssPhp\ScssPhp\Visitor\SelectorVisitor;
use SourceSpan\FileSpan;

/**
 * A type selector.
 *
 * This selects elements whose name equals the given name.
 *
 * @internal
 */
final class TypeSelector extends SimpleSelector
{
    public function __construct(/**
     * The element name being selected.
     */
        private readonly QualifiedName $name,
        FileSpan $span
    ) {
        parent::__construct($span);
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getSpecificity(): int
    {
        return 1;
    }

    public function accept(SelectorVisitor $visitor)
    {
        return $visitor->visitTypeSelector($this);
    }

    public function addSuffix(string $suffix): \ScssPhp\ScssPhp\Ast\Selector\TypeSelector
    {
        return new TypeSelector(new QualifiedName($this->name->getName() . $suffix, $this->name->getNamespace()), $this->getSpan());
    }

    public function unify(array $compound): ?array
    {
        $first = $compound[0] ?? null;

        if ($first instanceof UniversalSelector || $first instanceof TypeSelector) {
            $unified = ExtendUtil::unifyUniversalAndElement($this, $first);

            if ($unified === null) {
                return null;
            }

            $compound[0] = $unified;

            return $compound;
        }

        return array_merge([$this], $compound);
    }

    public function isSuperselector(SimpleSelector $other): bool
    {
        if (parent::isSuperselector($other)) {
            return true;
        }
        return $other instanceof TypeSelector && $this->name->getName() === $other->getName()->getName() && ($this->name->getNamespace() === '*' || $this->name->getNamespace() === $other->getName()->getNamespace());
    }

    public function equals(object $other): bool
    {
        return $other instanceof TypeSelector && $other->name->equals($this->name);
    }
}
