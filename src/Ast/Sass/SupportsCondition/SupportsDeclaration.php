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
namespace Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;

use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Source_Span\File_Span;
/**
 * A condition that selects for browsers where a given declaration is
 * supported.
 *
 * @internal
 */
final class Supports_Declaration implements Supports_Condition
{
    private readonly File_Span $span;
    public function __construct(
        /**
         * The name of the declaration being tested.
         */
        private readonly Expression $name,
        /**
         * The value of the declaration being tested.
         */
        private readonly Expression $value,
        File_Span $span
    )
    {
        $this->span = $span;
    }
    public function get_name(): Expression
    {
        return $this->name;
    }
    public function get_value(): Expression
    {
        return $this->value;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    /**
     * Returns whether this is a CSS Custom Property declaration.
     *
     * Note that this can return `false` for declarations that will ultimately be
     * serialized as custom properties if they aren't *parsed as* custom
     * properties, such as `#{--foo}: ...`.
     *
     * If this is `true`, then `value` will be a {@see StringExpression}.
     */
    public function is_custom_property(): bool
    {
        return $this->name instanceof String_Expression && !$this->name->has_quotes() && str_starts_with($this->name->get_text()->get_initial_plain(), '--');
    }
    public function __toString(): string
    {
        return "({$this->name}: {$this->value})";
    }
}