<?php

declare (strict_types=1);
namespace Scss_Php\Scss_Php\Ast\Selector;

use Scss_Php\Scss_Php\Visitor\Selector_Search_Visitor;
/**
 * A visitor for finding the first {@see ParentSelector} in a given selector.
 *
 * @template-extends SelectorSearchVisitor<ParentSelector>
 *
 * @internal
 */
final class Parent_Selector_Visitor extends Selector_Search_Visitor
{
    public function visit_parent_selector(Parent_Selector $selector): Parent_Selector
    {
        return $selector;
    }
}