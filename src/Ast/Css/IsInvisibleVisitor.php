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
namespace Scss_Php\Scss_Php\Ast\Css;

use Scss_Php\Scss_Php\Visitor\Every_Css_Visitor;
/**
 * The visitor used to implement {@see CssNode::isInvisible}
 *
 * @internal
 */
final class Is_Invisible_Visitor extends Every_Css_Visitor
{
    public function __construct(
        /**
         * Whether to consider selectors with bogus combinators invisible.
         */
        private readonly bool $include_bogus,
        /**
         * Whether to consider comments invisible.
         */
        private readonly bool $include_comments
    )
    {
    }
    public function visit_css_at_rule(Css_At_Rule $node): bool
    {
        // An unknown at-rule is never invisible. Because we don't know the semantics
        // of unknown rules, we can't guarantee that (for example) `@foo {}` isn't
        // meaningful.
        return false;
    }
    public function visit_css_comment(Css_Comment $node): bool
    {
        return $this->include_comments && !$node->is_preserved();
    }
    public function visit_css_style_rule(Css_Style_Rule $node): bool
    {
        return ($this->include_bogus ? $node->get_selector()->is_invisible() : $node->get_selector()->is_invisible_other_than_bogus_combinators()) || parent::visit_css_style_rule($node);
    }
}