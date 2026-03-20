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
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Source_Span\File_Span;
/**
 * The state of an extension for a given extender.
 *
 * The target of the extension is represented externally, in the map that
 * contains this extender.
 *
 * @internal
 */
class Extension
{
    /**
     * The extender (such as `A` in `A {@extend B}`).
     */
    public readonly Extender $extender;
    public readonly File_Span $span;
    /**
     * @param list<CssMediaQuery>|null $mediaContext
     */
    public function __construct(
        Complex_Selector $extender,
        /**
         * The selector that's being extended.
         */
        public readonly Simple_Selector $target,
        File_Span $span,
        /**
         * The media query context to which this extension is restricted, or `null`
         * if it can apply within any context.
         */
        public readonly ?array $media_context = null,
        public readonly bool $is_optional = false
    )
    {
        $this->extender = Extender::for_extension($extender, $this);
        $this->span = $span;
    }
    public function with_extender(Complex_Selector $new_extender): Extension
    {
        return new Extension($new_extender, $this->target, $this->span, $this->media_context, $this->is_optional);
    }
}