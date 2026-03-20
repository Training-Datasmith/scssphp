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

use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Util\Equatable_Util;
/**
 * A selector that's extending another selector, such as `A` in `A {@extend B}`.
 * @internal
 */
final class Extender
{
    /**
     * The minimum specificity required for any selector generated from this
     * extender.
     */
    public readonly int $specificity;
    private function __construct(
        public readonly Complex_Selector $selector,
        ?int $specificity = null,
        /**
         * Whether this extender represents a selector that was originally in the
         * document, rather than one defined with `@extend`.
         */
        public readonly bool $is_original = false,
        /**
         * The extension that created this Extender.
         *
         * Not all {@see Extender}s are created by extensions. Some simply represent the
         * original selectors that exist in the document.
         */
        private readonly ?Extension $extension = null
    )
    {
        $this->specificity = $specificity ?? $this->selector->get_specificity();
    }
    public static function create(Complex_Selector $selector, ?int $specificity = null, bool $original = false): self
    {
        return new Extender($selector, $specificity, $original);
    }
    public static function for_extension(Complex_Selector $selector, Extension $extension): self
    {
        return new Extender($selector, extension: $extension);
    }
    /**
     * @param list<CssMediaQuery>|null $mediaContext
     */
    public function assert_compatible_media_context(?array $media_context): void
    {
        if ($this->extension === null) {
            return;
        }
        $expected_media_context = $this->extension->media_context;
        if ($expected_media_context === null) {
            return;
        }
        if ($media_context !== null && Equatable_Util::list_equals($expected_media_context, $media_context)) {
            return;
        }
        throw new Simple_Sass_Exception('You may not @extend selectors across media queries.', $this->extension->span);
    }
}