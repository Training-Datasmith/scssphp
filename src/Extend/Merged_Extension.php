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
namespace Scss_Php\Scss_Php\Extend;

use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Util\Equatable_Util;
/**
 * An {@see Extension} created by merging two {@see Extension}s with the same extender
 * and target.
 *
 * This is used when multiple mandatory extensions exist to ensure that both of
 * them are marked as resolved.
 *
 * @internal
 */
final class Merged_Extension extends Extension
{
    private function __construct(public readonly Extension $left, public readonly Extension $right)
    {
        parent::__construct($this->left->extender->selector, $this->left->target, $this->left->span, $this->left->media_context ?? $this->right->media_context, true);
    }
    public static function merge(Extension $left, Extension $right): Extension
    {
        if (!Equatable_Util::equals($left->extender->selector, $right->extender->selector) || !Equatable_Util::equals($left->target, $right->target)) {
            throw new \InvalidArgumentException('$left and $right aren\'t the same extension.');
        }
        if ($left->media_context !== null && $right->media_context !== null && !Equatable_Util::list_equals($left->media_context, $right->media_context)) {
            $location = $left->span->message('');
            throw new Simple_Sass_Exception("From {$location}\nYou may not @extend the same selector from within different media queries.", $right->span);
        }
        // If one extension is optional and doesn't add a special media context, it
        // doesn't need to be merged.
        if ($right->is_optional && $right->media_context === null) {
            return $left;
        }
        if ($left->is_optional && $left->media_context === null) {
            return $right;
        }
        return new Merged_Extension($left, $right);
    }
    /**
     * Returns all leaf-node [Extension]s in the tree of [MergedExtension]s.
     *
     * @return \Traversable<Extension>
     */
    public function unmerge(): \Traversable
    {
        if ($this->left instanceof Merged_Extension) {
            yield from $this->left->unmerge();
        } else {
            yield $this->left;
        }
        if ($this->right instanceof Merged_Extension) {
            yield from $this->right->unmerge();
        } else {
            yield $this->right;
        }
    }
}