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
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Value;
/**
 * The result of evaluating arguments to a function or mixin.
 *
 * @internal
 */
final class Argument_Results
{
    /**
     * @param list<Value>            $positional
     * @param list<AstNode>          $positionalNodes
     * @param array<string, Value>   $named
     * @param array<string, AstNode> $namedNodes
     */
    public function __construct(
        /**
         * Arguments passed by position.
         */
        private readonly array $positional,
        /**
         * The {@see AstNode}s that hold the spans for each {@see positional} argument.
         */
        private readonly array $positional_nodes,
        private readonly array $named,
        /**
         * The {@see AstNode}s that hold the spans for each {@see named} argument.
         */
        private readonly array $named_nodes,
        private readonly List_Separator $separator
    )
    {
    }
    /**
     * @return list<Value>
     */
    public function get_positional(): array
    {
        return $this->positional;
    }
    /**
     * @return list<AstNode>
     */
    public function get_positional_nodes(): array
    {
        return $this->positional_nodes;
    }
    /**
     * @return array<string, Value>
     */
    public function get_named(): array
    {
        return $this->named;
    }
    /**
     * @return array<string, AstNode>
     */
    public function get_named_nodes(): array
    {
        return $this->named_nodes;
    }
    public function get_separator(): List_Separator
    {
        return $this->separator;
    }
}