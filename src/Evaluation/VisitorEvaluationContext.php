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
namespace Scss_Php\Scss_Php\Evaluation;

use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Deprecation;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Visitor_Evaluation_Context extends Evaluation_Context
{
    public function __construct(private readonly Evaluate_Visitor $visitor, private readonly Ast_Node $default_warn_node_with_span)
    {
    }
    public function get_current_callable_span(): File_Span
    {
        $callable_node = $this->visitor->get_callable_node();
        if ($callable_node !== null) {
            return $callable_node->get_span();
        }
        throw new \LogicException('No Sass callable is currently being evaluated.');
    }
    public function warn(string $message, ?Deprecation $deprecation = null): void
    {
        $span = $this->visitor->get_import_span() ?? $this->maybe_current_callable_span() ?? $this->default_warn_node_with_span->get_span();
        $this->visitor->warn($message, $span, $deprecation);
    }
    private function maybe_current_callable_span(): ?File_Span
    {
        $callable_node = $this->visitor->get_callable_node();
        if ($callable_node !== null) {
            return $callable_node->get_span();
        }
        return null;
    }
}