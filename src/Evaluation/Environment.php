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
use Scss_Php\Scss_Php\Sass_Callable\Sass_Callable;
use Scss_Php\Scss_Php\Sass_Callable\User_Defined_Callable;
use Scss_Php\Scss_Php\Value\Value;
use Source_Span\File_Span;
/**
 * The lexical environment in which Sass is executed.
 *
 * This tracks lexically-scoped information, such as variables, functions, and
 * mixins.
 *
 * @internal
 */
final class Environment
{
    /**
     * A map of variable names to their indices in {@see variables}.
     *
     * This map is filled in as-needed, and may not be complete.
     *
     * @var array<string, int>
     */
    private array $variable_indices = [];
    /**
     * A map of function names to their indices in {@see functions}.
     *
     * This map is filled in as-needed, and may not be complete.
     *
     * @var array<string, int>
     */
    private array $function_indices = [];
    /**
     * A map of mixin names to their indices in {@see mixins}.
     *
     * This map is filled in as-needed, and may not be complete.
     *
     * @var array<string, int>
     */
    private array $mixin_indices = [];
    /**
     * Whether the environment is lexically within a mixin.
     */
    private bool $in_mixin = false;
    /**
     * Whether the environment is currently in a global or semi-global scope.
     *
     * A semi-global scope can assign to global variables, but it doesn't declare
     * them by default.
     */
    private bool $in_semi_global_scope = true;
    /**
     * The name of the last variable that was accessed.
     *
     * This is cached to speed up repeated references to the same variable, as
     * well as references to the last variable's {@see FileSpan}.
     */
    private ?string $last_variable_name = null;
    /**
     * The index in {@see variables} of the last variable that was accessed.
     */
    private ?int $last_variable_index = null;
    public static function create(): Environment
    {
        return new Environment([new \ArrayObject()], [new \ArrayObject()], [new \ArrayObject()], [new \ArrayObject()]);
    }
    /**
     * @param array<int, \ArrayObject<string, Value>>        $variables
     * @param array<int, \ArrayObject<string, AstNode>>      $variableNodes
     * @param array<int, \ArrayObject<string, SassCallable>> $functions
     * @param array<int, \ArrayObject<string, SassCallable>> $mixins
     */
    private function __construct(
        /**
         * A list of variables defined at each lexical scope level.
         *
         * Each scope maps the names of declared variables to their values.
         *
         * The first element is the global scope, and each successive element is
         * deeper in the tree.
         */
        private array $variables,
        /**
         * The nodes where each variable in {@see variables} was defined.
         *
         * This stores {@see AstNode}s rather than {@see FileSpan}s so it can avoid calling
         * {@see AstNode::getSspan} if the span isn't required, since some nodes need to do
         * real work to manufacture a source span.
         */
        private array $variable_nodes,
        /**
         * A list of functions defined at each lexical scope level.
         *
         * Each scope maps the names of declared functions to their values.
         *
         * The first element is the global scope, and each successive element is
         * deeper in the tree.
         */
        private array $functions,
        /**
         * A list of mixins defined at each lexical scope level.
         *
         * Each scope maps the names of declared mixins to their values.
         *
         * The first element is the global scope, and each successive element is
         * deeper in the tree.
         */
        private array $mixins,
        /**
         * The content block passed to the lexically-enclosing mixin, or `null` if
         * this is not in a mixin, or if no content block was passed.
         */
        private ?User_Defined_Callable $content = null
    )
    {
    }
    public function get_content(): ?User_Defined_Callable
    {
        return $this->content;
    }
    /**
     * Whether the environment is lexically at the root of the document.
     */
    public function at_root(): bool
    {
        return \count($this->variables) === 1;
    }
    public function is_in_mixin(): bool
    {
        return $this->in_mixin;
    }
    /**
     * Creates a closure based on this environment.
     *
     * Any scope changes in this environment will not affect the closure.
     * However, any new declarations or assignments in scopes that are visible
     * when the closure was created will be reflected.
     */
    public function closure(): Environment
    {
        return new Environment($this->variables, $this->variable_nodes, $this->functions, $this->mixins, $this->content);
    }
    /**
     * Returns a new environment to use for an imported file.
     *
     * The returned environment shares this environment's variables, functions,
     * and mixins, but excludes most modules (except for global modules that
     * result from importing a file with forwards).
     */
    public function for_import(): Environment
    {
        return new Environment($this->variables, $this->variable_nodes, $this->functions, $this->mixins, $this->content);
    }
    public function get_variable(string $name): ?Value
    {
        if ($this->last_variable_name === $name) {
            assert($this->last_variable_index !== null);
            return $this->variables[$this->last_variable_index][$name] ?? null;
        }
        $index = $this->variable_indices[$name] ?? null;
        if ($index !== null) {
            $this->last_variable_name = $name;
            $this->last_variable_index = $index;
            return $this->variables[$index][$name] ?? null;
        }
        $index = $this->variable_index($name);
        if ($index === null) {
            return null;
        }
        $this->last_variable_name = $name;
        $this->last_variable_index = $index;
        $this->variable_indices[$name] = $index;
        return $this->variables[$index][$name] ?? null;
    }
    public function get_variable_node(string $name): ?Ast_Node
    {
        if ($this->last_variable_name === $name) {
            assert($this->last_variable_index !== null);
            return $this->variable_nodes[$this->last_variable_index][$name] ?? null;
        }
        $index = $this->variable_indices[$name] ?? null;
        if ($index !== null) {
            $this->last_variable_name = $name;
            $this->last_variable_index = $index;
            return $this->variable_nodes[$index][$name] ?? null;
        }
        $index = $this->variable_index($name);
        if ($index === null) {
            return null;
        }
        $this->last_variable_name = $name;
        $this->last_variable_index = $index;
        $this->variable_indices[$name] = $index;
        return $this->variable_nodes[$index][$name] ?? null;
    }
    /**
     * Returns whether a variable named $name exists.
     */
    public function variable_exists(string $name): bool
    {
        return $this->get_variable($name) !== null;
    }
    /**
     * Returns whether a global variable named $name exists.
     */
    public function global_variable_exists(string $name): bool
    {
        return isset($this->variables[0][$name]);
    }
    /**
     * Returns the index of the last map in {@see variables} that has a $name key,
     * or `null` if none exists.
     */
    private function variable_index(string $name): ?int
    {
        for ($i = \count($this->variables) - 1; $i >= 0; $i--) {
            if (isset($this->variables[$i][$name])) {
                return $i;
            }
        }
        return null;
    }
    /**
     * Sets the variable named $name to $value.
     *
     * If $global is `true`, this sets the variable at the top-level scope.
     * Otherwise, if the variable was already defined, it'll set it in the
     * previous scope. If it's undefined, it'll set it in the current scope.
     */
    public function set_variable(string $name, Value $value, Ast_Node $node_with_span, bool $global = false): void
    {
        if ($global || $this->at_root()) {
            // Don't set the index if there's already a variable with the given name,
            // since local accesses should still return the local variable.
            if (!isset($this->variable_indices[$name])) {
                $this->last_variable_name = $name;
                $this->last_variable_index = 0;
                $this->variable_indices[$name] = 0;
            }
            $this->variables[0][$name] = $value;
            $this->variable_nodes[0][$name] = $node_with_span;
            return;
        }
        if ($this->last_variable_name === $name) {
            assert($this->last_variable_index !== null);
            $index = $this->last_variable_index;
        } else {
            if (!isset($this->variable_indices[$name])) {
                $this->variable_indices[$name] = $this->variable_index($name) ?? \count($this->variables) - 1;
            }
            $index = $this->variable_indices[$name];
        }
        if (!$this->in_semi_global_scope && $index === 0) {
            $index = \count($this->variables) - 1;
            $this->variable_indices[$name] = $index;
        }
        $this->last_variable_name = $name;
        $this->last_variable_index = $index;
        $this->variables[$index][$name] = $value;
        $this->variable_nodes[$index][$name] = $node_with_span;
    }
    /**
     * Sets the variable named $name to $value.
     *
     * Unlike {@see setVariable}, this will declare the variable in the current scope
     * even if a declaration already exists in an outer scope.
     */
    public function set_local_variable(string $name, Value $value, Ast_Node $node_with_span): void
    {
        $index = \count($this->variables) - 1;
        $this->last_variable_name = $name;
        $this->last_variable_index = $index;
        $this->variable_indices[$name] = $index;
        $this->variables[$index][$name] = $value;
        $this->variable_nodes[$index][$name] = $node_with_span;
    }
    public function get_function(string $name): ?Sass_Callable
    {
        $index = $this->function_indices[$name] ?? null;
        if ($index !== null) {
            return $this->functions[$index][$name] ?? null;
        }
        $index = $this->function_index($name);
        if ($index === null) {
            return null;
        }
        $this->function_indices[$name] = $index;
        return $this->functions[$index][$name] ?? null;
    }
    /**
     * Returns the index of the last map in {@see functions} that has a $name key,
     * or `null` if none exists.
     */
    private function function_index(string $name): ?int
    {
        for ($i = \count($this->functions) - 1; $i >= 0; $i--) {
            if (isset($this->functions[$i][$name])) {
                return $i;
            }
        }
        return null;
    }
    /**
     * Returns whether a function named $name exists.
     */
    public function function_exists(string $name): bool
    {
        return $this->get_function($name) !== null;
    }
    public function set_function(Sass_Callable $callable): void
    {
        $index = \count($this->functions) - 1;
        $name = $callable->get_name();
        $this->function_indices[$name] = $index;
        $this->functions[$index][$name] = $callable;
    }
    public function get_mixin(string $name): ?Sass_Callable
    {
        $index = $this->mixin_indices[$name] ?? null;
        if ($index !== null) {
            return $this->mixins[$index][$name] ?? null;
        }
        $index = $this->mixin_index($name);
        if ($index === null) {
            return null;
        }
        $this->mixin_indices[$name] = $index;
        return $this->mixins[$index][$name] ?? null;
    }
    /**
     * Returns the index of the last map in {@see mixins} that has a $name key,
     * or `null` if none exists.
     */
    private function mixin_index(string $name): ?int
    {
        for ($i = \count($this->mixins) - 1; $i >= 0; $i--) {
            if (isset($this->mixins[$i][$name])) {
                return $i;
            }
        }
        return null;
    }
    /**
     * Returns whether a mixin named $name exists.
     */
    public function mixin_exists(string $name): bool
    {
        return $this->get_mixin($name) !== null;
    }
    public function set_mixin(Sass_Callable $callable): void
    {
        $index = \count($this->mixins) - 1;
        $name = $callable->get_name();
        $this->mixin_indices[$name] = $index;
        $this->mixins[$index][$name] = $callable;
    }
    /**
     * Sets $content as {@see content} for the duration of $callback.
     *
     * @param callable(): void $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    public function with_content(?User_Defined_Callable $content, callable $callback): void
    {
        $old_content = $this->content;
        $this->content = $content;
        $callback();
        $this->content = $old_content;
    }
    /**
     * Sets {@see inMixin} to `true` for the duration of $callback.
     *
     * @param callable(): void $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    public function as_mixin(callable $callback): void
    {
        $old_in_mixin = $this->in_mixin;
        $this->in_mixin = true;
        $callback();
        $this->in_mixin = $old_in_mixin;
    }
    /**
     * Runs $callback in a new scope.
     *
     * Variables, functions, and mixins declared in a given scope are
     * inaccessible outside of it. If $semiGlobal is passed, this scope can
     * assign to global variables without a `!global` declaration.
     *
     * If $when is false, this doesn't create a new scope and instead just
     * executes $callback and returns its result.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    public function scope(callable $callback, bool $when = true, bool $semi_global = false)
    {
        // We have to track semi-globalness even if `!$when` so that
        //
        //     div {
        //       @if ... {
        //         $x: y;
        //       }
        //     }
        //
        // doesn't assign to the global scope.
        $semi_global = $semi_global && $this->in_semi_global_scope;
        $was_in_semi_global_scope = $this->in_semi_global_scope;
        $this->in_semi_global_scope = $semi_global;
        if (!$when) {
            try {
                return $callback();
            } finally {
                $this->in_semi_global_scope = $was_in_semi_global_scope;
            }
        }
        $this->variables[] = new \ArrayObject();
        $this->variable_nodes[] = new \ArrayObject();
        $this->functions[] = new \ArrayObject();
        $this->mixins[] = new \ArrayObject();
        try {
            return $callback();
        } finally {
            $this->in_semi_global_scope = $was_in_semi_global_scope;
            $this->last_variable_name = null;
            $this->last_variable_index = null;
            $removed_variables = array_pop($this->variables);
            assert($removed_variables !== null);
            foreach ($removed_variables as $name => $_) {
                unset($this->variable_indices[$name]);
            }
            array_pop($this->variable_nodes);
            $removed_functions = array_pop($this->functions);
            assert($removed_functions !== null);
            foreach ($removed_functions as $name => $_) {
                unset($this->function_indices[$name]);
            }
            $removed_mixins = array_pop($this->mixins);
            assert($removed_mixins !== null);
            foreach ($removed_mixins as $name => $_) {
                unset($this->mixin_indices[$name]);
            }
        }
    }
}