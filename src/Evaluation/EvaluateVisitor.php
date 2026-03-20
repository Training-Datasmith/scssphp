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

use League\Uri\Uri;
use Scss_Php\Scss_Php\Ast\Ast_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Comment;
use Scss_Php\Scss_Php\Ast\Css\Css_Keyframe_Block;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Ast\Css\Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Node;
use Scss_Php\Scss_Php\Ast\Css\Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Css_Value;
use Scss_Php\Scss_Php\Ast\Css\Media_Query_Singleton_Merge_Result;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_At_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Comment;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Declaration;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Import;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Keyframe_Block;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Media_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Node;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Parent_Node;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Style_Rule;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Stylesheet;
use Scss_Php\Scss_Php\Ast\Css\Modifiable_Css_Supports_Rule;
use Scss_Php\Scss_Php\Ast\Fake_Ast_Node;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\At_Root_Query;
use Scss_Php\Scss_Php\Ast\Sass\Callable_Invocation;
use Scss_Php\Scss_Php\Ast\Sass\Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Binary_Operator;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Boolean_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Color_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\If_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Interpolated_Function_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Is_Calculation_Safe_Visitor;
use Scss_Php\Scss_Php\Ast\Sass\Expression\List_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Map_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Null_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Number_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Parenthesized_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Selector_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\String_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Supports_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operation_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Unary_Operator;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Value_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Expression\Variable_Expression;
use Scss_Php\Scss_Php\Ast\Sass\Import\Dynamic_Import;
use Scss_Php\Scss_Php\Ast\Sass\Import\Static_Import;
use Scss_Php\Scss_Php\Ast\Sass\Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Statement;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Root_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\At_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Block;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Content_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Debug_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Each_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Error_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\For_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Function_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\If_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Import_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Include_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Loud_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Media_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Mixin_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Return_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Silent_Comment;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Style_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Supports_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Variable_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Warn_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Statement\While_Rule;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Anything;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Declaration;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Function;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Interpolation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Negation;
use Scss_Php\Scss_Php\Ast\Sass\Supports_Condition\Supports_Operation;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Collection\Map;
use Scss_Php\Scss_Php\Colors;
use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Multi_Span_Sass_Runtime_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Runtime_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Runtime_Exception;
use Scss_Php\Scss_Php\Extend\Concrete_Extension_Store;
use Scss_Php\Scss_Php\Extend\Extension;
use Scss_Php\Scss_Php\Extend\Extension_Store;
use Scss_Php\Scss_Php\Function\Function_Registry;
use Scss_Php\Scss_Php\Importer\Import_Cache;
use Scss_Php\Scss_Php\Importer\Importer;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Interpolation_Map;
use Scss_Php\Scss_Php\Parser\Keyframe_Selector_Parser;
use Scss_Php\Scss_Php\Sass_Callable\Built_In_Callable;
use Scss_Php\Scss_Php\Sass_Callable\Plain_Css_Callable;
use Scss_Php\Scss_Php\Sass_Callable\Sass_Callable;
use Scss_Php\Scss_Php\Sass_Callable\User_Defined_Callable;
use Scss_Php\Scss_Php\Source_Span\Multi_Span;
use Scss_Php\Scss_Php\Stack_Trace\Frame;
use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Ast_Util;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Util\Logger_Util;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Scss_Php\Scss_Php\Value\Calculation_Operation;
use Scss_Php\Scss_Php\Value\Calculation_Operator;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Argument_List;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Calculation;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_Function;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Mixin;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Visitor\Expression_Visitor;
use Scss_Php\Scss_Php\Visitor\Statement_Visitor;
use Scss_Php\Scss_Php\Warn;
use Source_Span\File_Span;
use Source_Span\Simple_Source_Location;
use Source_Span\Source_File;
/**
 * A visitor that executes Sass code to produce a CSS tree.
 *
 * @template-implements StatementVisitor<Value|null>
 * @template-implements ExpressionVisitor<Value>
 *
 * @internal
 */
class Evaluate_Visitor implements Statement_Visitor, Expression_Visitor
{
    /**
     * @var array<string, SassCallable>
     */
    private array $built_in_functions = [];
    /**
     * A set of message/location pairs for warnings that have been emitted via
     * {@see warn}.
     *
     * We only want to emit one warning per location, to avoid blowing up users'
     * consoles with redundant warnings.
     *
     * @var array<string, array<string, true>>
     */
    private array $warnings_emitted = [];
    /**
     * The current lexical environment.
     */
    private Environment $environment;
    /**
     * The style rule that defines the current parent selector, if any.
     *
     * This doesn't take into consideration any intermediate `@at-root` rules. In
     * the common case where those rules are relevant, use {@see getStyleRule} instead.
     */
    private ?Modifiable_Css_Style_Rule $style_rule_ignoring_at_root = null;
    /**
     * The current media queries, if any.
     *
     * @var list<CssMediaQuery>|null
     */
    private ?array $media_queries = null;
    /**
     * The set of media queries that were merged together to create
     * {@see $mediaQueries}.
     *
     * This will be non-null if and only if {@see $mediaQueries} is non-null, but it
     * will be empty if {@see $mediaQueries} isn't the result of a merge.
     *
     * @var CssMediaQuery[]|null
     */
    private ?array $media_query_sources = null;
    private ?Modifiable_Css_Parent_Node $parent = null;
    /**
     * The name of the current declaration parent.
     */
    private ?string $declaration_name = null;
    /**
     * The human-readable name of the current stack frame.
     */
    private string $member = 'root stylesheet';
    /**
     * The innermost user-defined callable that's being invoked.
     */
    private ?User_Defined_Callable $current_callable = null;
    /**
     * The node for the innermost callable that's being invoked.
     *
     * This is used to produce warnings for function calls. It's stored as an
     * {@see AstNode} rather than a {@see FileSpan} so we can avoid calling {@see AstNode::getSpan}
     * if the span isn't required, since some nodes need to do real work to
     * manufacture a source span.
     */
    private ?Ast_Node $callable_node = null;
    /**
     * The span for the current import that's being resolved.
     *
     * This is used to produce warnings for importers.
     */
    private ?File_Span $import_span = null;
    /**
     * Whether we're currently executing a function.
     */
    private bool $in_function = false;
    /**
     * Whether we're currently building the output of an unknown at rule.
     */
    private bool $in_unknown_at_rule = false;
    /**
     * Whether we're directly within an `@at-root` rule that excludes style rules.
     */
    private bool $at_root_excluding_style_rule = false;
    /**
     * Whether we're currently building the output of a `@keyframes` rule.
     */
    private bool $in_key_frames = false;
    /**
     * Whether we're currently evaluating a {@see SupportsDeclaration}.
     *
     * When this is true, calculations will not be simplified.
     */
    private bool $in_supports_declaration = false;
    /**
     * The canonical URLs of all stylesheets loaded during compilation.
     *
     * @var array<string, true>
     */
    private array $loaded_urls = [];
    /**
     * A map from canonical URLs for modules (or imported files) that are
     * currently being evaluated to AST nodes whose spans indicate the original
     * loads for those modules.
     *
     * Map values may be `null`, which indicates an active module that doesn't
     * have a source span associated with its original load (such as the
     * entrypoint module).
     *
     * This is used to ensure that we don't get into an infinite load loop.
     *
     * @var array<string, AstNode|null>
     */
    private array $active_modules = [];
    /**
     * The dynamic call stack representing function invocations, mixin
     * invocations, and imports surrounding the current context.
     *
     * Each member is a tuple of the span where the stack trace starts and the
     * name of the member being invoked.
     *
     * This stores {@see AstNode}s rather than {@see FileSpan}s so it can avoid calling
     * {@see AstNode::getSpan} if the span isn't required, since some nodes need to do
     * real work to manufacture a source span.
     *
     * @var list<array{string, AstNode}>
     */
    private array $stack = [];
    /**
     * The importer that's currently being used to resolve relative imports.
     *
     * If this is `null`, relative imports aren't supported in the current
     * stylesheet.
     */
    private ?Importer $importer = null;
    /**
     * Whether we're in a dependency.
     *
     * A dependency is defined as a stylesheet imported by an importer other than
     * the original.
     */
    private bool $in_dependency = false;
    private ?Stylesheet $stylesheet = null;
    private ?Modifiable_Css_Stylesheet $root = null;
    private ?int $end_of_imports = null;
    /**
     * Plain-CSS imports that didn't appear in the initial block of CSS imports.
     *
     * These are added to the initial CSS import block by {@see visitStylesheet} after
     * the stylesheet has been fully performed.
     *
     * This is `null` unless there are any out-of-order imports in the current
     * stylesheet.
     *
     * @var list<ModifiableCssImport>|null
     */
    private ?array $out_of_order_imports = null;
    private ?Extension_Store $extension_store = null;
    /**
     * @param SassCallable[] $functions
     */
    public function __construct(
        /**
         * The import cache used to import other stylesheets.
         */
        private readonly Import_Cache $import_cache,
        array $functions,
        private readonly Logger_Interface $logger,
        /**
         * Whether to avoid emitting warnings for files loaded from dependencies.
         */
        private readonly bool $quiet_deps = false,
        /**
         * Whether to track source map information.
         */
        private readonly bool $source_map = false
    )
    {
        $this->environment = Environment::create();
        $sass_meta_uri = Uri::new('sass:meta');
        // These functions are defined in the context of the evaluator because
        // they need access to the environment or other local state.
        // When adding a new function here, its name must also be added in {@see FunctionRegistry::SPECIAL_META_GLOBAL_FUNCTIONS}.
        $meta_functions = [Built_In_Callable::function('global-variable-exists', '$name, $module: null', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean {
            $variable = $arguments[0]->assert_string('name');
            $module = $arguments[1]->real_null()?->assert_string('module');
            if ($module !== null) {
                // TODO remove this when implementing modules
                throw new Sass_Script_Exception('Sass modules are not implemented yet.');
            }
            return Sass_Boolean::create($this->environment->global_variable_exists(str_replace('_', '-', $variable->get_text())));
        }, $sass_meta_uri), Built_In_Callable::function('variable-exists', '$name', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean {
            $variable = $arguments[0]->assert_string('name');
            return Sass_Boolean::create($this->environment->variable_exists(str_replace('_', '-', $variable->get_text())));
        }, $sass_meta_uri), Built_In_Callable::function('function-exists', '$name, $module: null', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean {
            $variable = $arguments[0]->assert_string('name');
            $module = $arguments[1]->real_null()?->assert_string('module');
            if ($module !== null) {
                // TODO remove this when implementing modules
                throw new Sass_Script_Exception('Sass modules are not implemented yet.');
            }
            return Sass_Boolean::create($this->environment->function_exists(str_replace('_', '-', $variable->get_text())) || isset($this->built_in_functions[$variable->get_text()]) || Function_Registry::has($variable->get_text()));
        }, $sass_meta_uri), Built_In_Callable::function('mixin-exists', '$name, $module: null', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean {
            $variable = $arguments[0]->assert_string('name');
            $module = $arguments[1]->real_null()?->assert_string('module');
            if ($module !== null) {
                // TODO remove this when implementing modules
                throw new Sass_Script_Exception('Sass modules are not implemented yet.');
            }
            return Sass_Boolean::create($this->environment->mixin_exists(str_replace('_', '-', $variable->get_text())));
        }, $sass_meta_uri), Built_In_Callable::function('content-exists', '', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Boolean {
            if (!$this->environment->is_in_mixin()) {
                throw new Sass_Script_Exception('content-exists() may only be called within a mixin.');
            }
            return Sass_Boolean::create($this->environment->get_content() !== null);
        }, $sass_meta_uri), Built_In_Callable::function('get-function', '$name, $css: false, $module: null', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Function {
            $name = $arguments[0]->assert_string('name');
            $css = $arguments[1]->is_truthy();
            $module = $arguments[2]->real_null()?->assert_string('module');
            if ($css) {
                if ($module !== null) {
                    throw new Sass_Script_Exception('$css and $module may not both be passed at once.');
                }
                return new Sass_Function(new Plain_Css_Callable($name->get_text()));
            }
            \assert($this->callable_node !== null);
            $callable = $this->add_exception_span($this->callable_node, function () use ($name, $module): ?\Scss_Php\Scss_Php\Sass_Callable\Sass_Callable {
                $normalized_name = str_replace('_', '-', $name->get_text());
                $namespace = $module?->get_text();
                if ($namespace !== null) {
                    // TODO remove this when implementing modules
                    throw new Sass_Script_Exception('Sass modules are not implemented yet.');
                }
                $local = $this->environment->get_function($normalized_name);
                if ($local !== null) {
                    return $local;
                }
                return $this->get_builtin_function($normalized_name);
            });
            if ($callable === null) {
                throw new Sass_Script_Exception("Function not found: {$name}");
            }
            return new Sass_Function($callable);
        }, $sass_meta_uri), Built_In_Callable::function('get-mixin', '$name, $module: null', function ($arguments): \Scss_Php\Scss_Php\Value\Sass_Mixin {
            $name = $arguments[0]->assert_string('name');
            $module = $arguments[1]->real_null()?->assert_string('module');
            \assert($this->callable_node !== null);
            $callable = $this->add_exception_span($this->callable_node, function () use ($name, $module): ?\Scss_Php\Scss_Php\Sass_Callable\Sass_Callable {
                if ($module !== null) {
                    // TODO remove this when implementing modules
                    throw new Sass_Script_Exception('Sass modules are not implemented yet.');
                }
                return $this->environment->get_mixin(str_replace('_', '-', $name->get_text()));
            });
            if ($callable === null) {
                throw new Sass_Script_Exception("Mixin not found: {$name}");
            }
            return new Sass_Mixin($callable);
        }, $sass_meta_uri), Built_In_Callable::function('call', '$function, $args...', function (array $arguments) {
            $function = $arguments[0];
            $args = $arguments[1];
            \assert($args instanceof Sass_Argument_List);
            $callable_node = $this->callable_node;
            \assert($callable_node !== null);
            if (\count($args->get_keywords()) === 0) {
                $keyword_rest = null;
            } else {
                $keyword_args = new Map();
                foreach ($args->get_keywords() as $name => $value) {
                    $keyword_args->put(new Sass_String($name, false), $value);
                }
                $keyword_rest = new Value_Expression(Sass_Map::create($keyword_args), $callable_node->get_span());
            }
            $invocation = new Argument_Invocation([], [], $callable_node->get_span(), new Value_Expression($args, $callable_node->get_span()), $keyword_rest);
            if ($function instanceof Sass_String) {
                Warn::for_deprecation("Passing a string to call() is deprecated and will be illegal in Dart Sass 2.0.0.\n\nRecommendation: call(get-function({$function}))", Deprecation::callString);
                $expression = new Function_Expression($function->get_text(), $invocation, $callable_node->get_span());
                return $expression->accept($this);
            }
            $callable = $function->assert_function('function')->get_callable();
            return $this->run_function_callable($invocation, $callable, $callable_node);
        }, $sass_meta_uri)];
        foreach ($functions as $function) {
            $this->built_in_functions[str_replace('_', '-', $function->get_name())] = $function;
        }
        foreach ($meta_functions as $function) {
            $this->built_in_functions[$function->get_name()] = $function;
        }
    }
    public function get_callable_node(): ?Ast_Node
    {
        return $this->callable_node;
    }
    public function get_import_span(): ?File_Span
    {
        return $this->import_span;
    }
    /**
     * The current parent node in the output CSS tree.
     */
    private function get_parent(): Modifiable_Css_Parent_Node
    {
        if ($this->parent === null) {
            throw new \LogicException('Cannot access "getParent" outside of a module.');
        }
        return $this->parent;
    }
    private function get_style_rule(): ?Modifiable_Css_Style_Rule
    {
        return $this->at_root_excluding_style_rule ? null : $this->style_rule_ignoring_at_root;
    }
    /**
     * The stylesheet that's currently being evaluated.
     */
    private function get_stylesheet(): Stylesheet
    {
        if ($this->stylesheet === null) {
            throw new \LogicException('Cannot access "getStylesheet" outside of a module.');
        }
        return $this->stylesheet;
    }
    /**
     * The root stylesheet node.
     */
    private function get_root(): Modifiable_Css_Stylesheet
    {
        if ($this->root === null) {
            throw new \LogicException('Cannot access "getRoot" outside of a module.');
        }
        return $this->root;
    }
    /**
     * The first index in `$this->getRoot()->getChildren()` after the initial block of CSS imports.
     */
    private function get_end_of_imports(): int
    {
        if ($this->end_of_imports === null) {
            throw new \LogicException('Cannot access "getEndOfImports" outside of a module.');
        }
        return $this->end_of_imports;
    }
    /**
     * The extension store that tracks extensions and style rules for the current
     * module.
     */
    private function get_extension_store(): Extension_Store
    {
        if ($this->extension_store === null) {
            throw new \LogicException('Cannot access "getExtensionStore" outside of a module.');
        }
        return $this->extension_store;
    }
    /**
     * @param array<string, Value> $initialVariables
     */
    public function run(?Importer $importer, Stylesheet $node, array $initial_variables = []): Evaluate_Result
    {
        return Evaluation_Context::with_evaluation_context(new Visitor_Evaluation_Context($this, $node), function () use ($importer, $node, $initial_variables): \Scss_Php\Scss_Php\Evaluation\Evaluate_Result {
            $url = $node->get_span()->get_source_url();
            if ($url !== null) {
                $url_string = (string) $url;
                $this->active_modules[$url_string] = null;
                // TODO check how to handle stdin
                $this->loaded_urls[$url_string] = true;
            }
            /** @var ExtensionStore $extensionStore */
            [$css, $extension_store] = $this->add_exception_trace(fn(): array => $this->execute($importer, $node, $initial_variables));
            $selectors = $extension_store->get_simple_selectors();
            $unsatisfied_extension = Iterable_Util::first_or_null($extension_store->extensions_where_target(fn(Simple_Selector $target): bool => !Equatable_Util::iterable_contains($selectors, $target)));
            if ($unsatisfied_extension !== null) {
                $this->throw_for_unsatisfied_extension($unsatisfied_extension);
            }
            return new Evaluate_Result($css, array_keys($this->loaded_urls));
        });
    }
    /**
     * @param array<string, Value> $initialVariables
     *
     * @return array{CssStylesheet, ExtensionStore}
     */
    private function execute(?Importer $importer, Stylesheet $stylesheet, array $initial_variables = []): array
    {
        $environment = Environment::create();
        foreach ($initial_variables as $variable_name => $initial_variable) {
            $environment->set_variable($variable_name, $initial_variable, new Fake_Ast_Node(fn() => Source_File::from_string('')->span(0)));
        }
        $css = null;
        $extension_store = Concrete_Extension_Store::create();
        $this->with_environment($environment, function () use ($importer, $stylesheet, $extension_store, &$css): void {
            $old_importer = $this->importer;
            $old_stylesheet = $this->stylesheet;
            $old_root = $this->root;
            $old_parent = $this->parent;
            $old_end_of_imports = $this->end_of_imports;
            $old_out_of_order_imports = $this->out_of_order_imports;
            $old_extension_store = $this->extension_store;
            $old_style_rule = $this->get_style_rule();
            $old_media_queries = $this->media_queries;
            $old_declaration_name = $this->declaration_name;
            $old_in_unknown_at_rule = $this->in_unknown_at_rule;
            $old_at_root_excluding_style_rule = $this->at_root_excluding_style_rule;
            $old_in_keyframes = $this->in_key_frames;
            $this->importer = $importer;
            $this->stylesheet = $stylesheet;
            $this->root = $root = new Modifiable_Css_Stylesheet($stylesheet->get_span());
            $this->parent = $root;
            $this->end_of_imports = 0;
            $this->out_of_order_imports = null;
            $this->extension_store = $extension_store;
            $this->style_rule_ignoring_at_root = null;
            $this->media_queries = null;
            $this->declaration_name = null;
            $this->in_unknown_at_rule = false;
            $this->at_root_excluding_style_rule = false;
            $this->in_key_frames = false;
            $this->visit_stylesheet($stylesheet);
            $css = $this->out_of_order_imports === null ? $root : new Modifiable_Css_Stylesheet($stylesheet->get_span(), $this->add_out_of_order_imports());
            $this->importer = $old_importer;
            $this->stylesheet = $old_stylesheet;
            $this->root = $old_root;
            $this->parent = $old_parent;
            $this->end_of_imports = $old_end_of_imports;
            $this->out_of_order_imports = $old_out_of_order_imports;
            $this->extension_store = $old_extension_store;
            $this->style_rule_ignoring_at_root = $old_style_rule;
            $this->media_queries = $old_media_queries;
            $this->declaration_name = $old_declaration_name;
            $this->in_unknown_at_rule = $old_in_unknown_at_rule;
            $this->at_root_excluding_style_rule = $old_at_root_excluding_style_rule;
            $this->in_key_frames = $old_in_keyframes;
        });
        assert($css instanceof Css_Stylesheet);
        return [$css, $extension_store];
    }
    /**
     * Returns a copy of `$this->getRoot()->getChildren` with {@see outOfOrderImports} inserted
     * after {@see endOfImports}, if necessary.
     *
     * @return list<ModifiableCssNode>
     */
    private function add_out_of_order_imports(): array
    {
        if ($this->out_of_order_imports === null) {
            return $this->get_root()->get_children();
        }
        $children = $this->get_root()->get_children();
        array_splice($children, $this->get_end_of_imports(), 0, $this->out_of_order_imports);
        return array_values($children);
    }
    /**
     * Throws an exception indicating that $extension is unsatisfied.
     */
    private function throw_for_unsatisfied_extension(Extension $extension): never
    {
        throw new Simple_Sass_Exception("The target selector was not found.\nUse \"@extend {$extension->target} !optional\" to avoid this error.", $extension->span);
    }
    /**
     * @phpstan-impure
     */
    public function visit_stylesheet(Stylesheet $node): ?Value
    {
        foreach ($node->get_children() as $child) {
            $child->accept($this);
        }
        return null;
    }
    public function visit_at_root_rule(At_Root_Rule $node): ?Value
    {
        $unparsed_query = $node->get_query();
        if ($unparsed_query !== null) {
            [$resolved, $map] = $this->perform_interpolation_with_map($unparsed_query, true);
            $query = At_Root_Query::parse($resolved, $this->logger, null, $map);
        } else {
            $query = At_Root_Query::get_default();
        }
        $parent = $this->get_parent();
        /** @var ModifiableCssParentNode[] $included */
        $included = [];
        while (!$parent instanceof Css_Stylesheet) {
            if (!$query->excludes($parent)) {
                $included[] = $parent;
            }
            $grand_parent = $parent->get_parent();
            if ($grand_parent === null) {
                throw new \LogicException('CssNodes must have a CssStylesheet transitive parent node.');
            }
            $parent = $grand_parent;
        }
        $root = $this->trim_included($included);
        // If we didn't exclude any rules, we don't need to use the copies we might
        // have created.
        if ($root === $this->get_parent()) {
            $this->environment->scope(function () use ($node): void {
                foreach ($node->get_children() as $child) {
                    $child->accept($this);
                }
            }, $node->has_declarations());
            return null;
        }
        $inner_copy = $root;
        if (!empty($included)) {
            $inner_copy = $included[0]->copy_without_children();
            $outer_copy = $inner_copy;
            foreach (array_slice($included, 1) as $included_node) {
                $copy = $included_node->copy_without_children();
                $copy->add_child($outer_copy);
                $outer_copy = $copy;
            }
            $root->add_child($outer_copy);
        }
        $scope = $this->scope_for_at_root($node, $inner_copy, $query, $included);
        $scope(function () use ($node): void {
            foreach ($node->get_children() as $child) {
                $child->accept($this);
            }
        });
        return null;
    }
    /**
     * Returns a scope callback for $query.
     *
     * This returns a callback that adjusts various instance variables for its
     * duration, based on which rules are excluded by $query. It always assigns
     * {@see parent} to $newParent.
     *
     * @param ModifiableCssParentNode[] $included
     *
     * @return callable((callable(): void)): void
     */
    private function scope_for_at_root(At_Root_Rule $node, Modifiable_Css_Parent_Node $new_parent, At_Root_Query $query, array $included): callable
    {
        $scope = function (callable $callback) use ($new_parent, $node): void {
            // We can't use  *rent here because it'll add the node to the tree
            // in the wrong place.
            $old_parent = $this->parent;
            $this->parent = $new_parent;
            $this->environment->scope($callback, $node->has_declarations());
            $this->parent = $old_parent;
        };
        if ($query->excludes_style_rules()) {
            $inner_scope = $scope;
            $scope = function (callable $callback) use ($inner_scope): void {
                $old_at_root_excluding_style_rule = $this->at_root_excluding_style_rule;
                $this->at_root_excluding_style_rule = true;
                $inner_scope($callback);
                $this->at_root_excluding_style_rule = $old_at_root_excluding_style_rule;
            };
        }
        if ($this->media_queries !== null && $query->excludes_name('media')) {
            $inner_scope = $scope;
            $scope = function (callable $callback) use ($inner_scope): void {
                $this->with_media_queries(null, null, function () use ($inner_scope, $callback): void {
                    $inner_scope($callback);
                });
            };
        }
        if ($this->in_key_frames && $query->excludes_name('keyframes')) {
            $inner_scope = $scope;
            $scope = function (callable $callback) use ($inner_scope): void {
                $was_in_keyframes = $this->in_key_frames;
                $this->in_key_frames = false;
                $inner_scope($callback);
                $this->in_key_frames = $was_in_keyframes;
            };
        }
        if ($this->in_unknown_at_rule && !Iterable_Util::any($included, fn($parent): bool => $parent instanceof Css_At_Rule)) {
            $inner_scope = $scope;
            $scope = function (callable $callback) use ($inner_scope): void {
                $was_in_unknown_at_rule = $this->in_unknown_at_rule;
                $this->in_unknown_at_rule = false;
                $inner_scope($callback);
                $this->in_unknown_at_rule = $was_in_unknown_at_rule;
            };
        }
        return $scope;
    }
    /**
     * Destructively trims a trailing sublist from $nodes that matches the
     * current list of parents.
     *
     * $nodes should be a list of parents included by an `@at-root` rule, from
     * innermost to outermost. If it contains a trailing sublist that's
     * contiguous—meaning that each node is a direct parent of the node before
     * it—and whose final node is a direct child of {@see getRoot}, this removes that
     * sublist and returns the innermost removed parent.
     *
     * Otherwise, this leaves $nodes as-is and returns {@see getRoot}.
     *
     * @param ModifiableCssParentNode[] $nodes
     */
    private function trim_included(array &$nodes): Modifiable_Css_Parent_Node
    {
        if (empty($nodes)) {
            return $this->get_root();
        }
        $parent = $this->get_parent();
        $innermost_contiguous = null;
        foreach ($nodes as $i => $node) {
            while ($parent !== $node) {
                $innermost_contiguous = null;
                $grand_parent = $parent->get_parent();
                if ($grand_parent === null) {
                    throw new \LogicException('Expected the node to be an ancestor.');
                }
                $parent = $grand_parent;
            }
            $innermost_contiguous ??= $i;
            $grand_parent = $parent->get_parent();
            if ($grand_parent === null) {
                throw new \LogicException('Expected the node to be an ancestor.');
            }
            $parent = $grand_parent;
        }
        if ($parent !== $this->get_root()) {
            return $this->get_root();
        }
        $root = $nodes[$innermost_contiguous];
        array_splice($nodes, $innermost_contiguous);
        return $root;
    }
    public function visit_content_block(Content_Block $node): ?Value
    {
        throw new \BadMethodCallException('Evaluation handles @include and its content block together.');
    }
    public function visit_content_rule(Content_Rule $node): ?Value
    {
        $content = $this->environment->get_content();
        if ($content === null) {
            return null;
        }
        $this->run_user_defined_callable($node->get_arguments(), $content, $node, function () use ($content) {
            foreach ($content->get_declaration()->get_children() as $statement) {
                $statement->accept($this);
            }
            return null;
        });
        return null;
    }
    public function visit_debug_rule(Debug_Rule $node): ?Value
    {
        $value = $node->get_expression()->accept($this);
        $this->logger->debug($value instanceof Sass_String ? $value->get_text() : (string) $value, $node->get_span());
        return null;
    }
    public function visit_declaration(Declaration $node): ?Value
    {
        if ($this->get_style_rule() === null && !$this->in_unknown_at_rule && !$this->in_key_frames) {
            throw $this->exception('Declarations may only be used within style rules.', $node->get_span());
        }
        if ($this->declaration_name !== null && $node->is_custom_property()) {
            throw $this->exception('Declarations whose names begin with "--" may not be nested.', $node->get_span());
        }
        \assert($this->get_parent()->get_parent() !== null);
        $siblings = $this->get_parent()->get_parent()->get_children();
        $interleaved_rules = [];
        if (List_Util::last($siblings) !== $this->get_parent() && !($this->quiet_deps && ($this->in_dependency || ($this->current_callable?->is_in_dependency() ?? false)))) {
            $parent_offset = array_search($this->get_parent(), $siblings, true);
            if ($parent_offset === false) {
                $parent_offset = -1;
            }
            foreach (array_slice($siblings, $parent_offset + 1) as $sibling) {
                if ($sibling instanceof Css_Comment) {
                    continue;
                }
                if ($sibling instanceof Css_Style_Rule) {
                    $interleaved_rules[] = $sibling;
                    continue;
                }
                // Always warn for siblings that aren't style rules, because they
                // add no specificity and they're nested in the same parent as this
                // declaration.
                $this->warn(<<<'MESSAGE'
                Sass's behavior for declarations that appear after nested
                rules will be changing to match the behavior specified by CSS in an upcoming
                version. To keep the existing behavior, move the declaration above the nested
                rule. To opt into the new behavior, wrap the declaration in `& {}`.
                
                More info: https://sass-lang.com/d/mixed-decls
                MESSAGE, new Multi_Span($node->get_span(), 'declaration', ['nested rule' => $sibling->get_span()]), Deprecation::mixedDecls);
                $interleaved_rules = [];
            }
        }
        $name = $this->interpolation_to_value($node->get_name(), true);
        if ($this->declaration_name !== null) {
            $name = new Css_Value($this->declaration_name . '-' . $name->get_value(), $name->get_span());
        }
        $expression = $node->get_value();
        if ($expression !== null) {
            $value = $expression->accept($this);
            // If the value is an empty list, preserve it, because converting it to CSS
            // will throw an error that we want the user to see.
            if (!$value->is_blank() || empty($value->as_list())) {
                $value_span_for_map = null;
                if ($this->source_map && $node->get_value() !== null) {
                    $value_span_for_map = $this->expression_node($node->get_value())->get_span();
                }
                $this->get_parent()->add_child(new Modifiable_Css_Declaration($name, new Css_Value($value, $expression->get_span()), $node->get_span(), $node->is_custom_property(), $interleaved_rules, $interleaved_rules === [] ? null : $this->stack_trace($node->get_span()), $value_span_for_map));
            } elseif (str_starts_with($name->get_value(), '--')) {
                throw $this->exception('Custom property values may not be empty.', $expression->get_span());
            }
        }
        $children = $node->get_children();
        if ($children !== null) {
            $old_declaration_name = $this->declaration_name;
            $this->declaration_name = $name->get_value();
            $this->environment->scope(function () use ($children): void {
                foreach ($children as $child) {
                    $child->accept($this);
                }
            }, $node->has_declarations());
            $this->declaration_name = $old_declaration_name;
        }
        return null;
    }
    public function visit_each_rule(Each_Rule $node): ?Value
    {
        $list = $node->get_list()->accept($this);
        $node_with_span = $this->expression_node($node->get_list());
        if (\count($node->get_variables()) === 1) {
            $variable_name = $node->get_variables()[0];
            $set_variables = function (Value $value) use ($variable_name, $node_with_span): void {
                $this->environment->set_local_variable($variable_name, $this->without_slash($value, $node_with_span), $node_with_span);
            };
        } else {
            $variables = $node->get_variables();
            $set_variables = function (Value $value) use ($variables, $node_with_span): void {
                $this->set_multiple_variables($variables, $value, $node_with_span);
            };
        }
        return $this->environment->scope(fn() => $this->handle_return($list->as_list(), function ($element) use ($set_variables, $node): ?\Scss_Php\Scss_Php\Value\Value {
            $set_variables($element);
            return $this->handle_return($node->get_children(), fn(Statement $child) => $child->accept($this));
        }), true, true);
    }
    /**
     * Destructures $value and assigns it to $variables, as in an `@each`
     * statement.
     *
     * @param list<string> $variables
     */
    private function set_multiple_variables(array $variables, Value $value, Ast_Node $node_with_span): void
    {
        $list = $value->as_list();
        $min_length = min(\count($variables), \count($list));
        for ($i = 0; $i < $min_length; $i++) {
            $this->environment->set_local_variable($variables[$i], $this->without_slash($list[$i], $node_with_span), $node_with_span);
        }
        for ($i = $min_length; $i < \count($variables); $i++) {
            $this->environment->set_local_variable($variables[$i], Sass_Null::create(), $node_with_span);
        }
    }
    public function visit_error_rule(Error_Rule $node): ?Value
    {
        throw $this->exception((string) $node->get_expression()->accept($this), $node->get_span());
    }
    public function visit_extend_rule(Extend_Rule $node): ?Value
    {
        $style_rule = $this->get_style_rule();
        if ($style_rule === null || $this->declaration_name !== null) {
            throw $this->exception('@extend may only be used within style rules.', $node->get_span());
        }
        foreach ($style_rule->get_original_selector()->get_components() as $complex) {
            if (!$complex->is_bogus()) {
                continue;
            }
            $selector_string = trim($complex);
            $verb = $complex->is_useless() ? "can't" : "shouldn't";
            $this->warn("The selector \"{$selector_string}\" is invalid CSS and {$verb} be an extender.\nThis will be an error in Dart Sass 2.0.0.\n\nMore info: https://sass-lang.com/d/bogus-combinators", new Multi_Span(Span_Util::trim_right($complex->get_span()), 'invalid selector', ['@extend rule' => $node->get_span()]), Deprecation::bogusCombinators);
        }
        [$target_text, $target_map] = $this->perform_interpolation_with_map($node->get_selector(), true);
        $list = Selector_List::parse(String_Util::trim_ascii($target_text, true), $this->logger, $target_map, null, false);
        foreach ($list->get_components() as $complex) {
            $compound = $complex->get_single_compound();
            if ($compound === null) {
                // If the selector was a compound selector but not a simple
                // selector, emit a more explicit error.
                throw new Simple_Sass_Format_Exception('complex selectors may not be extended.', $complex->get_span());
            }
            $simple = $compound->get_single_simple();
            if ($simple === null) {
                $alternative_string = implode(', ', $compound->get_components());
                throw new Simple_Sass_Format_Exception("compound selectors may no longer be extended.\nConsider `@extend {$alternative_string}` instead.\nSee https://sass-lang.com/d/extend-compound for details.\n", $compound->get_span());
            }
            $this->get_extension_store()->add_extension($style_rule->get_selector(), $simple, $node, $this->media_queries);
        }
        return null;
    }
    public function visit_at_rule(At_Rule $node): ?Value
    {
        if ($this->declaration_name !== null) {
            throw $this->exception('At-rules may not be used within nested declarations.', $node->get_span());
        }
        $name = $this->interpolation_to_value($node->get_name());
        $value = $node->get_value() !== null ? $this->interpolation_to_value($node->get_value(), true, true) : null;
        $children = $node->get_children();
        if ($children === null) {
            $this->get_parent()->add_child(new Modifiable_Css_At_Rule($name, $node->get_span(), true, $value));
            return null;
        }
        $was_in_keyframes = $this->in_key_frames;
        $was_in_unknown_at_rule = $this->in_unknown_at_rule;
        if (Util::unvendor($name->get_value()) === 'keyframes') {
            $this->in_key_frames = true;
        } else {
            $this->in_unknown_at_rule = true;
        }
        $this->with_parent(new Modifiable_Css_At_Rule($name, $node->get_span(), false, $value), function () use ($children, $name): void {
            $style_rule = $this->get_style_rule();
            if ($style_rule === null || $this->in_key_frames || $name->get_value() === 'font-face') {
                // Special-cased at-rules within style blocks are pulled out to the
                // root. Equivalent to prepending "@at-root" on them.
                foreach ($children as $child) {
                    $child->accept($this);
                }
            } else {
                // If we're in a style rule, copy it into the at-rule so that
                // declarations immediately inside it have somewhere to go.
                //
                // For example, "a {@foo {b: c}}" should produce "@foo {a {b: c}}".
                $this->with_parent($style_rule->copy_without_children(), function () use ($children): void {
                    foreach ($children as $child) {
                        $child->accept($this);
                    }
                }, null, false);
            }
        }, fn($node) => $node instanceof Css_Style_Rule, $node->has_declarations());
        $this->in_unknown_at_rule = $was_in_unknown_at_rule;
        $this->in_key_frames = $was_in_keyframes;
        return null;
    }
    public function visit_for_rule(For_Rule $node): ?Value
    {
        /** @var SassNumber $fromNumber */
        $from_number = $this->add_exception_span($node->get_from(), fn() => $node->get_from()->accept($this)->assert_number());
        /** @var SassNumber $toNumber */
        $to_number = $this->add_exception_span($node->get_to(), fn() => $node->get_to()->accept($this)->assert_number());
        $from = $this->add_exception_span($node->get_from(), fn() => $from_number->assert_int());
        $to = $this->add_exception_span($node->get_to(), fn() => $to_number->coerce($from_number->get_numerator_units(), $from_number->get_denominator_units())->assert_int());
        $direction = $from > $to ? -1 : 1;
        if (!$node->is_exclusive()) {
            $to += $direction;
        }
        if ($from === $to) {
            return null;
        }
        return $this->environment->scope(function () use ($node, $from, $to, $direction, $from_number): ?\Scss_Php\Scss_Php\Value\Value {
            $node_with_span = $this->expression_node($node->get_from());
            for ($i = $from; $i !== $to; $i += $direction) {
                $this->environment->set_local_variable($node->get_variable(), Sass_Number::with_units($i, $from_number->get_numerator_units(), $from_number->get_denominator_units()), $node_with_span);
                $result = $this->handle_return($node->get_children(), fn(Statement $child) => $child->accept($this));
                if ($result !== null) {
                    return $result;
                }
            }
            return null;
        }, true, true);
    }
    public function visit_function_rule(Function_Rule $node): ?Value
    {
        $this->environment->set_function(new User_Defined_Callable($node, $this->environment->closure(), $this->in_dependency));
        return null;
    }
    public function visit_if_rule(If_Rule $node): ?Value
    {
        $clause = $node->get_last_clause();
        foreach ($node->get_clauses() as $clause_to_check) {
            if ($clause_to_check->get_expression()->accept($this)->is_truthy()) {
                $clause = $clause_to_check;
                break;
            }
        }
        if ($clause === null) {
            return null;
        }
        return $this->environment->scope(fn() => $this->handle_return($clause->get_children(), fn(Statement $child) => $child->accept($this)), $clause->has_declarations(), true);
    }
    public function visit_import_rule(Import_Rule $node): ?Value
    {
        foreach ($node->get_imports() as $import) {
            if ($import instanceof Dynamic_Import) {
                $this->visit_dynamic_import($import);
            } else {
                assert($import instanceof Static_Import);
                $this->visit_static_import($import);
            }
        }
        return null;
    }
    /**
     * Adds the stylesheet imported by $import to the current document.
     */
    private function visit_dynamic_import(Dynamic_Import $import): void
    {
        $this->with_stack_frame('@import', $import, function () use ($import): void {
            $result = $this->load_stylesheet($import->get_url_string(), $import->get_span(), true);
            $stylesheet = $result->get_stylesheet();
            $url = $stylesheet->get_span()->get_source_url();
            if ($url !== null) {
                $url_string = (string) $url;
                if (array_key_exists($url_string, $this->active_modules)) {
                    $previous_load = $this->active_modules[$url_string];
                    if ($previous_load !== null) {
                        throw $this->multi_span_exception('This file is already being loaded.', 'new load', ['original load' => $previous_load->get_span()]);
                    }
                    throw $this->exception('This file is already being loaded.');
                }
                $this->active_modules[$url_string] = $import;
            }
            $old_importer = $this->importer;
            $old_stylesheet = $this->stylesheet;
            $old_in_dependency = $this->in_dependency;
            $this->importer = $result->get_importer();
            $this->stylesheet = $stylesheet;
            $this->in_dependency = $result->is_dependency();
            $this->visit_stylesheet($stylesheet);
            $this->importer = $old_importer;
            $this->stylesheet = $old_stylesheet;
            $this->in_dependency = $old_in_dependency;
            if ($url !== null) {
                unset($this->active_modules[(string) $url]);
            }
        });
    }
    private function load_stylesheet(string $url, File_Span $span, bool $for_import = false): Loaded_Stylesheet
    {
        try {
            assert($this->import_span === null);
            $this->import_span = $span;
            $base_url_string = $this->get_stylesheet()->get_span()->get_source_url();
            $base_url = $base_url_string === null ? null : Uri::new($base_url_string);
            $result = $this->import_cache->canonicalize(Uri::new($url), $this->importer, $base_url, $for_import);
            if ($result !== null) {
                $canonical_url = $result->canonical_url;
                $importer = $result->importer;
                $original_url = $result->original_url;
                // Make sure we record the canonical URL as "loaded" even if the
                // actual load fails, because watchers should watch it to see if it
                // changes in a way that allows the load to succeed.
                $this->loaded_urls[$canonical_url->to_string()] = true;
                $is_dependency = $this->in_dependency || $importer !== $this->importer;
                $stylesheet = $this->import_cache->import_canonical($importer, $canonical_url, $original_url, $this->quiet_deps && $is_dependency);
                if ($stylesheet !== null) {
                    return new Loaded_Stylesheet($stylesheet, $importer, $is_dependency);
                }
            }
            throw new \Exception("Can't find stylesheet to import.");
        } catch (Sass_Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw $this->exception($e->get_message(), null, $e);
        } finally {
            $this->import_span = null;
        }
    }
    /**
     * Adds a CSS import for $import.
     */
    private function visit_static_import(Static_Import $import): void
    {
        $url = $this->interpolation_to_value($import->get_url());
        $modifiers = $import->get_modifiers() !== null ? $this->interpolation_to_value($import->get_modifiers()) : null;
        $node = new Modifiable_Css_Import($url, $import->get_span(), $modifiers);
        if ($this->get_parent() !== $this->get_root()) {
            $this->get_parent()->add_child($node);
        } elseif ($this->get_end_of_imports() === \count($this->get_root()->get_children())) {
            $this->get_root()->add_child($node);
            $this->end_of_imports++;
        } else {
            $this->out_of_order_imports[] = $node;
        }
    }
    /**
     * Evaluate a given $mixin with $arguments and $contentCallable
     */
    private function apply_mixin(?Sass_Callable $mixin, ?User_Defined_Callable $content_callable, Argument_Invocation $arguments, Ast_Node $node_with_span, Ast_Node $node_with_span_without_content): void
    {
        if ($mixin === null) {
            throw $this->exception('Undefined mixin.', $node_with_span->get_span());
        }
        if ($mixin instanceof Built_In_Callable && !$mixin->accepts_content() && $content_callable !== null) {
            $evaluated = $this->evaluate_arguments($arguments);
            /** @var ArgumentDeclaration $overload */
            [$overload] = $mixin->callback_for(\count($evaluated->get_positional()), $evaluated->get_named());
            throw new Multi_Span_Sass_Runtime_Exception("Mixin doesn't accept a content block.", $node_with_span_without_content->get_span(), 'invocation', ['declaration' => $overload->get_span_with_name()], $this->stack_trace($node_with_span_without_content->get_span()));
        }
        if ($mixin instanceof Built_In_Callable) {
            $this->environment->with_content($content_callable, fn() => $this->environment->as_mixin(function () use ($arguments, $mixin, $node_with_span_without_content): void {
                $this->run_built_in_callable($arguments, $mixin, $node_with_span_without_content);
            }));
        } elseif ($mixin instanceof User_Defined_Callable) {
            $declaration = $mixin->get_declaration();
            assert($declaration instanceof Mixin_Rule);
            if ($content_callable !== null && !$declaration->has_content()) {
                throw new Multi_Span_Sass_Runtime_Exception("Mixin doesn't accept a content block.", $node_with_span_without_content->get_span(), 'invocation', ['declaration' => $mixin->get_declaration()->get_arguments()->get_span_with_name()], $this->stack_trace($node_with_span_without_content->get_span()));
            }
            $this->run_user_defined_callable($arguments, $mixin, $node_with_span_without_content, function () use ($content_callable, $declaration, $node_with_span_without_content) {
                $this->environment->with_content($content_callable, fn() => $this->environment->as_mixin(function () use ($declaration, $node_with_span_without_content): void {
                    foreach ($declaration->get_children() as $statement) {
                        $this->add_error_span($node_with_span_without_content, fn() => $statement->accept($this));
                    }
                }));
                return null;
            });
        } else {
            throw new \LogicException('Unknown callable type ' . $mixin::class);
        }
    }
    public function visit_include_rule(Include_Rule $node): ?Value
    {
        $mixin = $this->add_exception_span($node, fn() => $this->environment->get_mixin($node->get_name()));
        if (str_starts_with($node->get_original_name(), '--') && $mixin instanceof User_Defined_Callable && !str_starts_with($mixin->get_declaration()->get_original_name(), '--')) {
            $this->warn("Sass @mixin names beginning with -- are deprecated for forward-compatibility with plain CSS mixins.\n\nFor details, see https://sass-lang.com/d/css-function-mixin", $node->get_name_span(), Deprecation::cssFunctionMixin);
        }
        $content_callable = null;
        if ($node->get_content() !== null) {
            $content_callable = new User_Defined_Callable($node->get_content(), $this->environment->closure(), $this->in_dependency);
        }
        $node_with_span_without_content = new Fake_Ast_Node(fn() => $node->get_span_without_content());
        $this->apply_mixin($mixin, $content_callable, $node->get_arguments(), $node, $node_with_span_without_content);
        return null;
    }
    public function visit_mixin_rule(Mixin_Rule $node): ?Value
    {
        $this->environment->set_mixin(new User_Defined_Callable($node, $this->environment->closure(), $this->in_dependency));
        return null;
    }
    public function visit_loud_comment(Loud_Comment $node): ?Value
    {
        if ($this->in_function) {
            return null;
        }
        // Comments are allowed to appear between CSS imports.
        if ($this->get_parent() === $this->get_root() && $this->get_end_of_imports() === \count($this->get_root()->get_children())) {
            $this->end_of_imports++;
        }
        $text = $this->perform_interpolation($node->get_text());
        // Indented syntax doesn't require */
        if (!str_ends_with($text, '*/')) {
            $text .= ' */';
        }
        $this->get_parent()->add_child(new Modifiable_Css_Comment($text, $node->get_span()));
        return null;
    }
    public function visit_media_rule(Media_Rule $node): ?Value
    {
        if ($this->declaration_name !== null) {
            throw $this->exception('Media rules may not be used within nested declarations.', $node->get_span());
        }
        $queries = $this->visit_media_queries($node->get_query());
        $merged_queries = $this->media_queries !== null ? $this->merge_media_queries($this->media_queries, $queries) : null;
        if ($merged_queries === []) {
            return null;
        }
        if ($merged_queries === null) {
            $merged_sources = [];
        } else {
            assert($this->media_query_sources !== null);
            assert($this->media_queries !== null);
            $merged_sources = array_merge($this->media_query_sources, $this->media_queries, $queries);
        }
        $this->with_parent(new Modifiable_Css_Media_Rule($merged_queries ?? $queries, $node->get_span()), function () use ($merged_queries, $merged_sources, $queries, $node): void {
            $this->with_media_queries($merged_queries ?? $queries, $merged_sources, function () use ($node): void {
                $style_rule = $this->get_style_rule();
                if ($style_rule !== null) {
                    // If we're in a style rule, copy it into the media query so that
                    // declarations immediately inside @media have somewhere to go.
                    //
                    // For example, "a {@media screen {b: c}}" should produce
                    // "@media screen {a {b: c}}".
                    $this->with_parent($style_rule->copy_without_children(), function () use ($node): void {
                        foreach ($node->get_children() as $child) {
                            $child->accept($this);
                        }
                    }, null, false);
                } else {
                    foreach ($node->get_children() as $child) {
                        $child->accept($this);
                    }
                }
            });
        }, function ($node) use ($merged_sources): bool {
            if ($node instanceof Css_Style_Rule) {
                return true;
            }
            if ($merged_sources !== [] && $node instanceof Css_Media_Rule) {
                return Iterable_Util::every($node->get_queries(), fn(Css_Media_Query $query) => \in_array($query, $merged_sources, true));
            }
            return false;
        }, $node->has_declarations());
        return null;
    }
    /**
     * @return list<CssMediaQuery>
     */
    private function visit_media_queries(Interpolation $interpolation): array
    {
        [$resolved, $map] = $this->perform_interpolation_with_map($interpolation, true);
        return Css_Media_Query::parse_list($resolved, $this->logger, null, $map);
    }
    /**
     * Returns a list of queries that selects for contexts that match both
     * $queries1 and $queries2.
     *
     * Returns the empty list if there are no contexts that match both $queries1
     * and $queries2, or `null` if there are contexts that can't be represented
     * by media queries.
     *
     * @param CssMediaQuery[] $queries1
     * @param CssMediaQuery[] $queries2
     *
     * @return list<CssMediaQuery>|null
     */
    private function merge_media_queries(array $queries1, array $queries2): ?array
    {
        $queries = [];
        foreach ($queries1 as $query1) {
            foreach ($queries2 as $query2) {
                $result = $query1->merge($query2);
                if ($result === Media_Query_Singleton_Merge_Result::empty) {
                    continue;
                }
                if ($result === Media_Query_Singleton_Merge_Result::unrepresentable) {
                    return null;
                }
                // Always true but not detected due to https://github.com/jiripudil/phpstan-sealed-classes/issues/2
                \assert($result instanceof Css_Media_Query);
                $queries[] = $result;
            }
        }
        return $queries;
    }
    public function visit_return_rule(Return_Rule $node): ?Value
    {
        return $this->without_slash($node->get_expression()->accept($this), $node->get_expression());
    }
    public function visit_silent_comment(Silent_Comment $node): ?Value
    {
        return null;
    }
    public function visit_style_rule(Style_Rule $node): ?Value
    {
        if ($this->declaration_name !== null) {
            throw $this->exception('Style rules may not be used within nested declarations.', $node->get_span());
        }
        if ($this->in_key_frames && $this->get_parent() instanceof Css_Keyframe_Block) {
            throw $this->exception('Style rules may not be used within keyframe blocks.', $node->get_span());
        }
        [$selector_text, $selector_map] = $this->perform_interpolation_with_map($node->get_selector(), true);
        if ($this->in_key_frames) {
            $parsed_selector = (new Keyframe_Selector_Parser($selector_text, $this->logger, null, $selector_map))->parse();
            $rule = new Modifiable_Css_Keyframe_Block(new Css_Value($parsed_selector, $node->get_selector()->get_span()), $node->get_span());
            $this->with_parent($rule, function () use ($node): void {
                foreach ($node->get_children() as $child) {
                    $child->accept($this);
                }
            }, fn($node) => $node instanceof Css_Style_Rule, $node->has_declarations());
            return null;
        }
        $parsed_selector = Selector_List::parse($selector_text, $this->logger, $selector_map, plainCss: $this->get_stylesheet()->is_plain_css());
        $nest = !($this->get_style_rule()?->is_from_plain_css() ?? false);
        if ($nest) {
            if ($this->get_stylesheet()->is_plain_css()) {
                foreach ($parsed_selector->get_components() as $complex) {
                    if (\count($complex->get_leading_combinators()) > 0) {
                        throw $this->exception("Top-level leading combinators aren't allowed in plain CSS.", $complex->get_leading_combinators()[0]->get_span());
                    }
                }
            }
            $parsed_selector = $parsed_selector->nest_within($this->style_rule_ignoring_at_root?->get_original_selector(), !$this->at_root_excluding_style_rule, $this->get_stylesheet()->is_plain_css());
        }
        $selector = $this->get_extension_store()->add_selector($parsed_selector, $this->media_queries);
        $rule = new Modifiable_Css_Style_Rule($selector, $node->get_span(), $parsed_selector, $this->get_stylesheet()->is_plain_css());
        $old_at_root_excluding_style_rule = $this->at_root_excluding_style_rule;
        $this->at_root_excluding_style_rule = false;
        $this->with_parent($rule, function () use ($rule, $node): void {
            $this->with_style_rule($rule, function () use ($node): void {
                foreach ($node->get_children() as $child) {
                    $child->accept($this);
                }
            });
        }, $nest ? fn($node): bool => $node instanceof Css_Style_Rule : null, $node->has_declarations());
        $this->at_root_excluding_style_rule = $old_at_root_excluding_style_rule;
        $this->warn_for_bogus_combinators($rule);
        if ($this->get_style_rule() === null && \count($this->get_parent()->get_children()) > 0) {
            $last_child = List_Util::last($this->get_parent()->get_children());
            $last_child->set_group_end(true);
        }
        return null;
    }
    private function warn_for_bogus_combinators(Css_Style_Rule $rule): void
    {
        if (!$rule->is_invisible_other_than_bogus_combinators()) {
            foreach ($rule->get_selector()->get_components() as $complex) {
                if (!$complex->is_bogus()) {
                    continue;
                }
                $selector_string = trim($complex);
                if ($complex->is_useless()) {
                    $this->warn("The selector \"{$selector_string}\" is invalid CSS. It will be omitted from the generated CSS.\nThis will be an error in Dart Sass 2.0.0.\n\nMore info: https://sass-lang.com/d/bogus-combinators", Span_Util::trim_right($complex->get_span()), Deprecation::bogusCombinators);
                } elseif (\count($complex->get_leading_combinators()) > 0) {
                    if (!$this->get_stylesheet()->is_plain_css()) {
                        $this->warn("The selector \"{$selector_string}\" is invalid CSS.\nThis will be an error in Dart Sass 2.0.0.\n\nMore info: https://sass-lang.com/d/bogus-combinators", Span_Util::trim_right($complex->get_span()), Deprecation::bogusCombinators);
                    }
                } else {
                    $omitted_message = $complex->is_bogus_other_than_leading_combinator() ? ' It will be omitted from the generated CSS.' : '';
                    $suffix = Iterable_Util::every($rule->get_children(), fn(Css_Node $child): bool => $child instanceof Css_Comment) ? "\n(try converting to a //-style comment)" : '';
                    $this->warn("The selector \"{$selector_string}\" is only valid for nesting and shouldn't\nhave children other than style rules.{$omitted_message}\nThis will be an error in Dart Sass 2.0.0.\n\nMore info: https://sass-lang.com/d/bogus-combinators", new Multi_Span(Span_Util::trim_right($complex->get_span()), 'invalid selector', ['this is not a style rule' . $suffix => $rule->get_children()[0]->get_span()]), Deprecation::bogusCombinators);
                }
            }
        }
    }
    public function visit_supports_rule(Supports_Rule $node): ?Value
    {
        if ($this->declaration_name !== null) {
            throw $this->exception('Supports rules may not be used within nested declarations.', $node->get_span());
        }
        $condition = new Css_Value($this->visit_supports_condition($node->get_condition()), $node->get_condition()->get_span());
        $this->with_parent(new Modifiable_Css_Supports_Rule($condition, $node->get_span()), function () use ($node): void {
            $style_rule = $this->get_style_rule();
            if ($style_rule !== null) {
                // If we're in a style rule, copy it into the supports rule so that
                // declarations immediately inside @supports have somewhere to go.
                //
                // For example, "a {@supports (a: b) {b: c}}" should produce "@supports
                // (a: b) {a {b: c}}".
                $this->with_parent($style_rule->copy_without_children(), function () use ($node): void {
                    foreach ($node->get_children() as $child) {
                        $child->accept($this);
                    }
                });
            } else {
                foreach ($node->get_children() as $child) {
                    $child->accept($this);
                }
            }
        }, fn($node) => $node instanceof Css_Style_Rule, $node->has_declarations());
        return null;
    }
    private function visit_supports_condition(Supports_Condition $condition): string
    {
        if ($condition instanceof Supports_Operation) {
            return sprintf('%s %s %s', $this->parenthesize($condition->get_left(), $condition->get_operator()), $condition->get_operator(), $this->parenthesize($condition->get_right(), $condition->get_operator()));
        }
        if ($condition instanceof Supports_Negation) {
            return 'not ' . $this->parenthesize($condition->get_condition());
        }
        if ($condition instanceof Supports_Interpolation) {
            return $this->evaluate_to_css($condition->get_expression(), false);
        }
        if ($condition instanceof Supports_Declaration) {
            return $this->with_supports_declaration(fn() => sprintf('(%s:%s%s)', $this->evaluate_to_css($condition->get_name()), $condition->is_custom_property() ? '' : ' ', $this->evaluate_to_css($condition->get_value())));
        }
        if ($condition instanceof Supports_Function) {
            return sprintf('%s(%s)', $this->perform_interpolation($condition->get_name()), $this->perform_interpolation($condition->get_arguments()));
        }
        if ($condition instanceof Supports_Anything) {
            return '(' . $this->perform_interpolation($condition->get_contents()) . ')';
        }
        throw new \InvalidArgumentException('Unknown supports condition type ' . $condition::class);
    }
    /**
     * Runs $callback in a context where {@see $inSupportsDeclaration} is true.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    private function with_supports_declaration(callable $callback)
    {
        $old_in_supports_declaration = $this->in_supports_declaration;
        $this->in_supports_declaration = true;
        try {
            return $callback();
        } finally {
            $this->in_supports_declaration = $old_in_supports_declaration;
        }
    }
    private function parenthesize(Supports_Condition $condition, ?string $operator = null): string
    {
        if ($condition instanceof Supports_Negation || $condition instanceof Supports_Operation && $operator !== $condition->get_operator()) {
            return '(' . $this->visit_supports_condition($condition) . ')';
        }
        return $this->visit_supports_condition($condition);
    }
    public function visit_variable_declaration(Variable_Declaration $node): ?Value
    {
        if ($node->is_guarded()) {
            $value = $this->add_exception_span($node, fn() => $this->environment->get_variable($node->get_name()));
            if ($value !== null && $value !== Sass_Null::create()) {
                return null;
            }
        }
        if ($node->is_global() && !$this->environment->global_variable_exists($node->get_name())) {
            $this->warn($this->environment->at_root() ? "As of Dart Sass 2.0.0, !global assignments won't be able to declare new variables.\n\nSince this assignment is at the root of the stylesheet, the !global flag is\nunnecessary and can safely be removed." : "As of Dart Sass 2.0.0, !global assignments won't be able to declare new variables.\n\nRecommendation: add `{$node->get_original_name()}: null` at the stylesheet root.", $node->get_span(), Deprecation::newGlobal);
        }
        $value = $this->without_slash($node->get_expression()->accept($this), $node->get_expression());
        $this->add_exception_span($node, function () use ($value, $node): void {
            $this->environment->set_variable($node->get_name(), $value, $this->expression_node($node->get_expression()), $node->is_global());
        });
        return null;
    }
    public function visit_warn_rule(Warn_Rule $node): ?Value
    {
        $value = $this->add_exception_span($node, fn() => $node->get_expression()->accept($this));
        $this->logger->warn($value instanceof Sass_String ? $value->get_text() : $this->serialize($value, $node->get_expression()), null, null, $this->stack_trace($node->get_span()));
        return null;
    }
    public function visit_while_rule(While_Rule $node): ?Value
    {
        return $this->environment->scope(function () use ($node): ?\Scss_Php\Scss_Php\Value\Value {
            $iteration_count = 0;
            $max_iterations = 10000;
            while ($node->get_condition()->accept($this)->is_truthy()) {
                if (++$iteration_count > $max_iterations) {
                    throw new Simple_Sass_Exception('@while loop exceeded maximum iteration limit of ' . $max_iterations, $node->get_span());
                }
                $result = $this->handle_return($node->get_children(), fn(Statement $child) => $child->accept($this));
                if ($result !== null) {
                    return $result;
                }
            }
            return null;
        }, $node->has_declarations(), true);
    }
    // ## Expressions
    public function visit_binary_operation_expression(Binary_Operation_Expression $node): Value
    {
        if ($this->get_stylesheet()->is_plain_css() && $node->get_operator() !== Binary_Operator::SINGLE_EQUALS && $node->get_operator() !== Binary_Operator::DIVIDED_BY) {
            throw $this->exception("Operators aren't allowed in plain CSS.", $node->get_operator_span());
        }
        return $this->add_exception_span($node, function () use ($node) {
            $left = $node->get_left()->accept($this);
            return match ($node->get_operator()) {
                Binary_Operator::SINGLE_EQUALS => $left->single_equals($node->get_right()->accept($this)),
                Binary_Operator::OR => $left->is_truthy() ? $left : $node->get_right()->accept($this),
                Binary_Operator::AND => $left->is_truthy() ? $node->get_right()->accept($this) : $left,
                Binary_Operator::EQUALS => Sass_Boolean::create($left->equals($node->get_right()->accept($this))),
                Binary_Operator::NOT_EQUALS => Sass_Boolean::create(!$left->equals($node->get_right()->accept($this))),
                Binary_Operator::GREATER_THAN => $left->greater_than($node->get_right()->accept($this)),
                Binary_Operator::GREATER_THAN_OR_EQUALS => $left->greater_than_or_equals($node->get_right()->accept($this)),
                Binary_Operator::LESS_THAN => $left->less_than($node->get_right()->accept($this)),
                Binary_Operator::LESS_THAN_OR_EQUALS => $left->less_than_or_equals($node->get_right()->accept($this)),
                Binary_Operator::PLUS => $left->plus($node->get_right()->accept($this)),
                Binary_Operator::MINUS => $left->minus($node->get_right()->accept($this)),
                Binary_Operator::TIMES => $left->times($node->get_right()->accept($this)),
                Binary_Operator::DIVIDED_BY => $this->slash($left, $node->get_right()->accept($this), $node),
                Binary_Operator::MODULO => $left->modulo($node->get_right()->accept($this)),
            };
        });
    }
    /**
     * Returns the result of the SassScript `/` operation between $left and
     * $right in $node.
     */
    private function slash(Value $left, Value $right, Binary_Operation_Expression $node): Value
    {
        $result = $left->divided_by($right);
        if ($left instanceof Sass_Number && $right instanceof Sass_Number && $node->allows_slash() && $this->operand_allows_slash($node->get_left()) && $this->operand_allows_slash($node->get_right())) {
            assert($result instanceof Sass_Number);
            return $result->with_slash($left, $right);
        }
        if ($left instanceof Sass_Number && $right instanceof Sass_Number) {
            $recommendation = function (Expression $expression) use (&$recommendation): string {
                if ($expression instanceof Binary_Operation_Expression && $expression->get_operator() === Binary_Operator::DIVIDED_BY) {
                    $left_recommendation = $recommendation($expression->get_left());
                    $right_recommendation = $recommendation($expression->get_right());
                    return "math.div({$left_recommendation}, {$right_recommendation})";
                }
                if ($expression instanceof Parenthesized_Expression) {
                    return (string) $expression->get_expression();
                }
                return (string) $expression;
            };
            $calc_recommendation = Ast_Util::expression_to_calc($node);
            $message = <<<WARNING
            Using / for division outside of calc() is deprecated and will be removed in Dart Sass 2.0.0.
            
            Recommendation: {$recommendation($node)} or {$calc_recommendation}
            
            More info and automated migrator: https://sass-lang.com/d/slash-div
            WARNING;
            $this->warn($message, $node->get_span(), Deprecation::slashDiv);
            return $result;
        }
        return $result;
    }
    /**
     * Returns whether $node can be used as a component of a slash-separated
     * number.
     *
     * Although this logic is mostly resolved at parse-time, we can't tell
     * whether operands will be evaluated as calculations until evaluation-time.
     */
    private function operand_allows_slash(Expression $node): bool
    {
        if (!$node instanceof Function_Expression) {
            return true;
        }
        if ($node->get_namespace() !== null) {
            return false;
        }
        return \in_array(strtolower($node->get_name()), ['calc', 'clamp', 'hypot', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'sqrt', 'exp', 'sign', 'mod', 'rem', 'atan2', 'pow', 'log'], true) && $this->environment->get_function($node->get_name()) === null;
    }
    public function visit_value_expression(Value_Expression $node): Value
    {
        return $node->get_value();
    }
    public function visit_variable_expression(Variable_Expression $node): Value
    {
        $result = $this->add_exception_span($node, fn() => $this->environment->get_variable($node->get_name()));
        if ($result !== null) {
            return $result;
        }
        throw $this->exception('Undefined variable.', $node->get_span());
    }
    public function visit_unary_operation_expression(Unary_Operation_Expression $node): Value
    {
        $operand = $node->get_operand()->accept($this);
        return $this->add_exception_span($node, fn() => match ($node->get_operator()) {
            Unary_Operator::PLUS => $operand->unary_plus(),
            Unary_Operator::MINUS => $operand->unary_minus(),
            Unary_Operator::DIVIDE => $operand->unary_divide(),
            Unary_Operator::NOT => $operand->unary_not(),
        });
    }
    public function visit_boolean_expression(Boolean_Expression $node): Value
    {
        return Sass_Boolean::create($node->get_value());
    }
    public function visit_if_expression(If_Expression $node): Value
    {
        [$positional, $named] = $this->evaluate_macro_arguments($node);
        $this->verify_arguments(\count($positional), $named, If_Expression::get_declaration(), $node);
        $condition = $positional[0] ?? $named['condition'];
        $if_true = $positional[1] ?? $named['if-true'];
        $if_false = $positional[2] ?? $named['if-false'];
        $result = $condition->accept($this)->is_truthy() ? $if_true : $if_false;
        return $this->without_slash($result->accept($this), $this->expression_node($result));
    }
    public function visit_null_expression(Null_Expression $node): Value
    {
        return Sass_Null::create();
    }
    public function visit_number_expression(Number_Expression $node): Value
    {
        return Sass_Number::create($node->get_value(), $node->get_unit());
    }
    public function visit_parenthesized_expression(Parenthesized_Expression $node): Value
    {
        if ($this->get_stylesheet()->is_plain_css()) {
            throw $this->exception("Parentheses aren't allowed in plain CSS.", $node->get_span());
        }
        return $node->get_expression()->accept($this);
    }
    public function visit_color_expression(Color_Expression $node): Value
    {
        return $node->get_value();
    }
    public function visit_list_expression(List_Expression $node): Value
    {
        return new Sass_List(array_map(fn(Expression $expression) => $expression->accept($this), $node->get_contents()), $node->get_separator(), $node->has_brackets());
    }
    public function visit_map_expression(Map_Expression $node): Value
    {
        /** @var Map<Value> $map */
        $map = new Map();
        /** @var Map<AstNode> $keyNodes */
        $key_nodes = new Map();
        foreach ($node->get_pairs() as $pair) {
            $key_value = $pair[0]->accept($this);
            $value_value = $pair[1]->accept($this);
            $old_value = $map->get($key_value);
            if ($old_value !== null) {
                $old_value_span = $key_nodes->get($key_value)?->get_span();
                throw new Multi_Span_Sass_Runtime_Exception('Duplicate key.', $pair[0]->get_span(), 'second key', $old_value_span !== null ? ['first key' => $old_value_span] : [], $this->stack_trace($pair[0]->get_span()));
            }
            $map->put($key_value, $value_value);
            $key_nodes->put($key_value, $pair[0]);
        }
        return Sass_Map::create($map);
    }
    private function get_builtin_function(string $name): ?Sass_Callable
    {
        if (!isset($this->built_in_functions[$name]) && Function_Registry::has($name)) {
            $this->built_in_functions[$name] = Function_Registry::get($name);
        }
        return $this->built_in_functions[$name] ?? null;
    }
    public function visit_function_expression(Function_Expression $node): Value
    {
        $function = $this->get_stylesheet()->is_plain_css() ? null : $this->add_exception_span($node, fn() => $this->environment->get_function($node->get_name()));
        if ($function === null) {
            if ($node->get_namespace() !== null) {
                throw $this->exception('Undefined function.', $node->get_span());
            }
            switch (strtolower($node->get_name())) {
                case 'min':
                case 'max':
                case 'round':
                case 'abs':
                    if ($node->get_arguments()->get_named() === [] && $node->get_arguments()->get_rest() === null && Iterable_Util::every($node->get_arguments()->get_positional(), fn(Expression $argument) => $argument->accept(new Is_Calculation_Safe_Visitor()))) {
                        return $this->visit_calculation($node, true);
                    }
                    break;
                case 'calc':
                case 'clamp':
                case 'hypot':
                case 'sin':
                case 'cos':
                case 'tan':
                case 'asin':
                case 'acos':
                case 'atan':
                case 'sqrt':
                case 'exp':
                case 'sign':
                case 'mod':
                case 'rem':
                case 'atan2':
                case 'pow':
                case 'log':
                    return $this->visit_calculation($node);
            }
            $function = ($this->get_stylesheet()->is_plain_css() ? null : $this->get_builtin_function($node->get_name())) ?? new Plain_Css_Callable($node->get_original_name());
        }
        if (str_starts_with($node->get_original_name(), '--') && $function instanceof User_Defined_Callable && !str_starts_with($function->get_declaration()->get_original_name(), '--')) {
            $this->warn("Sass @function names beginning with -- are deprecated for forward-compatibility with plain CSS functions.\n\nFor details, see https://sass-lang.com/d/css-function-mixin", $node->get_name_span(), Deprecation::cssFunctionMixin);
        }
        $old_in_function = $this->in_function;
        $this->in_function = true;
        $result = $this->add_error_span($node, fn() => $this->run_function_callable($node->get_arguments(), $function, $node));
        $this->in_function = $old_in_function;
        return $result;
    }
    private function visit_calculation(Function_Expression $node, bool $in_legacy_sass_function = false): Value
    {
        if ($node->get_arguments()->get_named() !== []) {
            throw $this->exception("Keyword arguments can't be used with calculations.", $node->get_span());
        }
        if ($node->get_arguments()->get_rest() !== null) {
            throw $this->exception("Rest arguments can't be used with calculations.", $node->get_span());
        }
        $this->check_calculation_arguments($node);
        $arguments = array_map(fn(\Scss_Php\Scss_Php\Ast\Sass\Expression $argument) => $this->visit_calculation_expression($argument, $in_legacy_sass_function), $node->get_arguments()->get_positional());
        if ($this->in_supports_declaration) {
            return Sass_Calculation::unsimplified($node->get_name(), $arguments);
        }
        $old_callable_node = $this->callable_node;
        $this->callable_node = $node;
        try {
            return match (strtolower($node->get_name())) {
                'calc' => Sass_Calculation::calc($arguments[0]),
                'sqrt' => Sass_Calculation::sqrt($arguments[0]),
                'sin' => Sass_Calculation::sin($arguments[0]),
                'cos' => Sass_Calculation::cos($arguments[0]),
                'tan' => Sass_Calculation::tan($arguments[0]),
                'asin' => Sass_Calculation::asin($arguments[0]),
                'acos' => Sass_Calculation::acos($arguments[0]),
                'atan' => Sass_Calculation::atan($arguments[0]),
                'abs' => Sass_Calculation::abs($arguments[0]),
                'exp' => Sass_Calculation::exp($arguments[0]),
                'sign' => Sass_Calculation::sign($arguments[0]),
                'min' => Sass_Calculation::min($arguments),
                'max' => Sass_Calculation::max($arguments),
                'hypot' => Sass_Calculation::hypot($arguments),
                'pow' => Sass_Calculation::pow($arguments[0], $arguments[1] ?? null),
                'atan2' => Sass_Calculation::atan2($arguments[0], $arguments[1] ?? null),
                'log' => Sass_Calculation::log($arguments[0], $arguments[1] ?? null),
                'mod' => Sass_Calculation::mod($arguments[0], $arguments[1] ?? null),
                'rem' => Sass_Calculation::rem($arguments[0], $arguments[1] ?? null),
                'round' => Sass_Calculation::round($arguments[0], $arguments[1] ?? null, $arguments[2] ?? null),
                'clamp' => Sass_Calculation::clamp($arguments[0], $arguments[1] ?? null, $arguments[2] ?? null),
                default => throw new \UnexpectedValueException(sprintf('Unknown calculation name "%s".', $node->get_name())),
            };
        } catch (Sass_Script_Exception $e) {
            // The simplification logic in the SassCalculation static methods will
            // throw an error if the arguments aren't compatible, but we have access
            // to the original spans so we can throw a more informative error.
            if (str_contains($e->get_message(), 'compatible')) {
                $this->verify_compatible_numbers($arguments, $node->get_arguments()->get_positional());
            }
            throw $this->exception($e->get_message(), $node->get_span(), $e);
        } finally {
            $this->callable_node = $old_callable_node;
        }
    }
    private function check_calculation_arguments(Function_Expression $node): void
    {
        $check = function (?int $max_args = null) use ($node): void {
            if ($node->get_arguments()->get_positional() === []) {
                throw $this->exception('Missing argument.', $node->get_span());
            }
            if ($max_args !== null && \count($node->get_arguments()->get_positional()) > $max_args) {
                throw $this->exception(sprintf('Only %d %s allowed, but %d %s passed.', $max_args, String_Util::pluralize('argument', $max_args), \count($node->get_arguments()->get_positional()), String_Util::pluralize('was', \count($node->get_arguments()->get_positional()), 'were')), $node->get_span());
            }
        };
        match (strtolower($node->get_name())) {
            'calc', 'sqrt', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'abs', 'exp', 'sign' => $check(1),
            'min', 'max', 'hypot' => $check(),
            'pow', 'atan2', 'log', 'mod', 'rem' => $check(2),
            'round', 'clamp' => $check(3),
            default => throw new \UnexpectedValueException(sprintf('Unknown calculation name "%s".', $node->get_name())),
        };
    }
    /**
     * Verifies that $args all have compatible units that can be used for CSS
     * calculations, and throws a {@see SassException} if not.
     *
     * The $nodesWithSpans should correspond to the spans for $args.
     *
     * @param object[]  $args
     * @param AstNode[] $nodesWithSpans
     *
     * @throws SassException
     */
    private function verify_compatible_numbers(array $args, array $nodes_with_spans): void
    {
        for ($i = 0; $i < \count($args); $i++) {
            $arg = $args[$i];
            if ($arg instanceof Sass_Number && $arg->has_complex_units()) {
                throw $this->exception("Number {$arg} isn't compatible with CSS calculations.", $nodes_with_spans[$i]->get_span());
            }
        }
        for ($i = 0; $i < \count($args); $i++) {
            $number1 = $args[$i];
            if (!$number1 instanceof Sass_Number) {
                continue;
            }
            for ($j = $i + 1; $j < \count($args); $j++) {
                $number2 = $args[$j];
                if (!$number2 instanceof Sass_Number) {
                    continue;
                }
                if ($number1->has_possibly_compatible_units($number2)) {
                    continue;
                }
                throw new Multi_Span_Sass_Runtime_Exception("{$number1} and {$number2} are incompatible.", $nodes_with_spans[$i]->get_span(), (string) $number1, [(string) $number2 => $nodes_with_spans[$j]->get_span()], $this->stack_trace($nodes_with_spans[$i]->get_span()));
            }
        }
    }
    /**
     * Evaluates $node as a component of a calculation.
     *
     * If $inLegacySassFunction is `true`, this allows unitless numbers to be added and
     * subtracted with numbers with units, for backwards-compatibility with the
     * old global `min()`, `max()`, `round()` and `abs()` functions.
     *
     * @return SassNumber|CalculationOperation|SassString|SassCalculation|Value
     */
    private function visit_calculation_expression(Expression $node, bool $in_legacy_sass_function): object
    {
        if ($node instanceof Parenthesized_Expression) {
            $result = $this->visit_calculation_expression($node->get_expression(), $in_legacy_sass_function);
            return $result instanceof Sass_String ? new Sass_String('(' . $result->get_text() . ')', false) : $result;
        }
        if ($node instanceof String_Expression) {
            if (!$node->accept(new Is_Calculation_Safe_Visitor())) {
                throw $this->exception("This expression can't be used in a calculation.", $node->get_span());
            }
            assert(!$node->has_quotes());
            $text = $node->get_text()->get_as_plain();
            if ($text === null) {
                return new Sass_String($this->perform_interpolation($node->get_text()), false);
            }
            return match (strtolower($text)) {
                'pi' => Sass_Number::create(M_PI),
                'e' => Sass_Number::create(M_E),
                'infinity' => Sass_Number::create(INF),
                '-infinity' => Sass_Number::create(-INF),
                'nan' => Sass_Number::create(NAN),
                default => new Sass_String($text, false),
            };
        }
        if ($node instanceof Binary_Operation_Expression) {
            $this->check_whitespace_around_calculation_operator($node);
            return $this->add_exception_span($node, fn() => Sass_Calculation::operate_internal($this->binary_operator_to_calculation_operator($node->get_operator(), $node), $this->visit_calculation_expression($node->get_left(), $in_legacy_sass_function), $this->visit_calculation_expression($node->get_right(), $in_legacy_sass_function), $in_legacy_sass_function, !$this->in_supports_declaration));
        }
        if ($node instanceof Number_Expression || $node instanceof Variable_Expression || $node instanceof Function_Expression || $node instanceof If_Expression) {
            $result = $node->accept($this);
            if ($result instanceof Sass_Number || $result instanceof Sass_Calculation) {
                return $result;
            }
            if ($result instanceof Sass_String && !$result->has_quotes()) {
                return $result;
            }
            throw $this->exception("Value {$result} can't be used in a calculation.", $node->get_span());
        }
        if ($node instanceof List_Expression && !$node->has_brackets() && $node->get_separator() === List_Separator::SPACE && \count($node->get_contents()) > 1) {
            $elements = [];
            foreach ($node->get_contents() as $element) {
                $elements[] = $this->visit_calculation_expression($element, $in_legacy_sass_function);
            }
            $this->check_adjacent_calculation_values($elements, $node);
            foreach ($elements as $i => $element) {
                if ($element instanceof Calculation_Operation && $node->get_contents()[$i] instanceof Parenthesized_Expression) {
                    $elements[$i] = new Sass_String("({$element})", false);
                }
            }
            return new Sass_String(implode(' ', $elements), false);
        }
        \assert(!$node->accept(new Is_Calculation_Safe_Visitor()));
        throw $this->exception("This expression can't be used in a calculation.", $node->get_span());
    }
    /**
     * Throws an error if $node requires whitespace around its operator in a
     * calculation but doesn't have it.
     */
    private function check_whitespace_around_calculation_operator(Binary_Operation_Expression $node): void
    {
        if ($node->get_operator() !== Binary_Operator::PLUS && $node->get_operator() !== Binary_Operator::MINUS) {
            return;
        }
        // We _should_ never be able to violate these conditions since we always
        // parse binary operations from a single file, but it's better to be safe
        // than have this crash bizarrely.
        if ($node->get_left()->get_span()->get_file() !== $node->get_right()->get_span()->get_file()) {
            return;
        }
        if ($node->get_left()->get_span()->get_end()->get_offset() >= $node->get_right()->get_span()->get_start()->get_offset()) {
            return;
        }
        $text_between_operands = $node->get_left()->get_span()->get_file()->get_text($node->get_left()->get_span()->get_end()->get_offset(), $node->get_right()->get_span()->get_start()->get_offset());
        $first = $text_between_operands[0];
        $last = $text_between_operands[\strlen($text_between_operands) - 1];
        if (!(Character::is_whitespace($first) || $first === '/') || !(Character::is_whitespace($last) || $last === '/')) {
            throw $this->exception('"+" and "-" must be surrounded by whitespace in calculations.', $node->get_operator_span());
        }
    }
    /**
     * Returns the {@see CalculationOperator} that corresponds to $operator.
     */
    private function binary_operator_to_calculation_operator(Binary_Operator $operator, Binary_Operation_Expression $node): Calculation_Operator
    {
        return match ($operator) {
            Binary_Operator::PLUS => Calculation_Operator::PLUS,
            Binary_Operator::MINUS => Calculation_Operator::MINUS,
            Binary_Operator::TIMES => Calculation_Operator::TIMES,
            Binary_Operator::DIVIDED_BY => Calculation_Operator::DIVIDED_BY,
            default => throw $this->exception("This operation can't be used in a calculation.", $node->get_operator_span()),
        };
    }
    /**
     * @param list<object> $elements
     */
    private function check_adjacent_calculation_values(array $elements, List_Expression $node): void
    {
        \assert(\count($elements) > 1);
        for ($i = 1; $i < \count($elements); $i++) {
            $previous = $elements[$i - 1];
            $current = $elements[$i];
            if ($previous instanceof Sass_String) {
                continue;
            }
            if ($current instanceof Sass_String) {
                continue;
            }
            $previous_node = $node->get_contents()[$i - 1];
            $current_node = $node->get_contents()[$i];
            if ($current_node instanceof Unary_Operation_Expression && ($current_node->get_operator() === Unary_Operator::MINUS || $current_node->get_operator() === Unary_Operator::PLUS) || $current_node instanceof Number_Expression && $current_node->get_value() < 0) {
                // `calc(1 -2)` parses as a space-separated list whose second value is a
                // unary operator or a negative number, but just saying it's an invalid
                // expression doesn't help the user understand what's going wrong. We
                // add special case error handling to help clarify the issue.
                throw $this->exception('"+" and "-" must be surrounded by whitespace in calculations.', $current_node->get_span()->subspan(0, 1));
            }
            throw $this->exception('Missing math operator.', $previous_node->get_span()->expand($current_node->get_span()));
        }
    }
    public function visit_interpolated_function_expression(Interpolated_Function_Expression $node): Value
    {
        $function = new Plain_Css_Callable($this->perform_interpolation($node->get_name()));
        $old_in_function = $this->in_function;
        $this->in_function = true;
        $result = $this->add_error_span($node, fn() => $this->run_function_callable($node->get_arguments(), $function, $node));
        $this->in_function = $old_in_function;
        return $result;
    }
    /**
     * @template V of Value|null
     *
     * @param callable(): V $run
     *
     * @return V
     *
     * @param-immediately-invoked-callable $run
     */
    private function run_user_defined_callable(Argument_Invocation $arguments, User_Defined_Callable $callable, Ast_Node $node_with_span, callable $run): ?Value
    {
        $evaluated = $this->evaluate_arguments($arguments);
        $name = $callable->get_name();
        if ($name !== '@content') {
            $name .= '()';
        }
        $old_callable = $this->current_callable;
        $this->current_callable = $callable;
        $result = $this->with_stack_frame(
            $name,
            $node_with_span,
            // Add an extra closure() call so that modifications to the environment
            // don't affect the underlying environment closure.
            fn() => $this->with_environment($callable->get_environment()->closure(), fn() => $this->environment->scope(function () use ($callable, $evaluated, $node_with_span, $run) {
                $this->verify_arguments(\count($evaluated->get_positional()), $evaluated->get_named(), $callable->get_declaration()->get_arguments(), $node_with_span);
                $declared_arguments = $callable->get_declaration()->get_arguments()->get_arguments();
                $min_length = min(\count($evaluated->get_positional()), \count($declared_arguments));
                for ($i = 0; $i < $min_length; $i++) {
                    $this->environment->set_local_variable($declared_arguments[$i]->get_name(), $evaluated->get_positional()[$i], $evaluated->get_positional_nodes()[$i]);
                }
                $named = $evaluated->get_named();
                $named_nodes = $evaluated->get_named_nodes();
                for ($i = \count($evaluated->get_positional()); $i < \count($declared_arguments); $i++) {
                    $argument = $declared_arguments[$i];
                    if (isset($named[$argument->get_name()])) {
                        $value = $named[$argument->get_name()];
                        unset($named[$argument->get_name()]);
                        $node_for_span = $named_nodes[$argument->get_name()];
                    } else {
                        assert($argument->get_default_value() !== null);
                        $value = $this->without_slash($argument->get_default_value()->accept($this), $this->expression_node($argument->get_default_value()));
                        $node_for_span = $this->expression_node($argument->get_default_value());
                    }
                    $this->environment->set_local_variable($argument->get_name(), $value, $node_for_span);
                }
                $argument_list = null;
                $rest_argument = $callable->get_declaration()->get_arguments()->get_rest_argument();
                if ($rest_argument !== null) {
                    $rest = array_values(array_slice($evaluated->get_positional(), \count($declared_arguments)));
                    $argument_list = new Sass_Argument_List($rest, $named, $evaluated->get_separator() === List_Separator::UNDECIDED ? List_Separator::COMMA : $evaluated->get_separator());
                    $this->environment->set_local_variable($rest_argument, $argument_list, $node_with_span);
                }
                $result = $run();
                if ($argument_list === null) {
                    return $result;
                }
                if ($named === []) {
                    return $result;
                }
                if ($argument_list->were_keyword_accessed()) {
                    return $result;
                }
                $unknown_names = array_keys($named);
                $last_name = array_pop($unknown_names);
                $message = sprintf('No argument%s named $%s%s.', $unknown_names ? 's' : '', $unknown_names ? implode(', $', $unknown_names) . ' or $' : '', $last_name);
                throw new Multi_Span_Sass_Runtime_Exception($message, $node_with_span->get_span(), 'invocation', ['declaration' => $callable->get_declaration()->get_arguments()->get_span_with_name()], $this->stack_trace($node_with_span->get_span()));
            }))
        );
        $this->current_callable = $old_callable;
        return $result;
    }
    private function run_function_callable(Argument_Invocation $arguments, ?Sass_Callable $callable, Ast_Node $node_with_span): Value
    {
        if ($callable instanceof Built_In_Callable) {
            return $this->without_slash($this->run_built_in_callable($arguments, $callable, $node_with_span), $node_with_span);
        }
        if ($callable instanceof User_Defined_Callable) {
            return $this->run_user_defined_callable($arguments, $callable, $node_with_span, function () use ($callable): \Scss_Php\Scss_Php\Value\Value {
                foreach ($callable->get_declaration()->get_children() as $statement) {
                    $return_value = $statement->accept($this);
                    if ($return_value instanceof Value) {
                        return $return_value;
                    }
                }
                throw $this->exception('Function finished without @return.', $callable->get_declaration()->get_span());
            });
        }
        if ($callable instanceof Plain_Css_Callable) {
            if (\count($arguments->get_named()) > 0 || $arguments->get_keyword_rest() !== null) {
                throw $this->exception("Plain CSS functions don't support keyword arguments.", $node_with_span->get_span());
            }
            $buffer = $callable->get_name() . '(';
            try {
                $first = true;
                foreach ($arguments->get_positional() as $argument) {
                    if ($first) {
                        $first = false;
                    } else {
                        $buffer .= ', ';
                    }
                    $buffer .= $this->evaluate_to_css($argument);
                }
                $rest_arg = $arguments->get_rest();
                if ($rest_arg !== null) {
                    $rest = $rest_arg->accept($this);
                    if (!$first) {
                        $buffer .= ', ';
                    }
                    $buffer .= $this->serialize($rest, $rest_arg);
                }
            } catch (Sass_Runtime_Exception $e) {
                if (!str_ends_with($e->get_original_message(), "isn't a valid CSS value.")) {
                    throw $e;
                }
                throw new Multi_Span_Sass_Runtime_Exception($e->get_original_message(), $e->get_span(), 'value', ['unknown function treated as plain CSS' => $node_with_span->get_span()], $e->get_sass_trace());
            }
            $buffer .= ')';
            return new Sass_String($buffer, false);
        }
        throw new \InvalidArgumentException('Unknown callable type ' . get_debug_type($callable) . '.');
    }
    private function run_built_in_callable(Argument_Invocation $arguments, Built_In_Callable $callable, Ast_Node $node_with_span): Value
    {
        $evaluated = $this->evaluate_arguments($arguments);
        $old_callable_node = $this->callable_node;
        $this->callable_node = $node_with_span;
        /** @var ArgumentDeclaration $overload */
        [$overload, $callback] = $callable->callback_for(\count($evaluated->get_positional()), $evaluated->get_named());
        $this->add_exception_span($node_with_span, function () use ($overload, $evaluated): void {
            $overload->verify(\count($evaluated->get_positional()), $evaluated->get_named());
        });
        $declared_arguments = $overload->get_arguments();
        $positional = $evaluated->get_positional();
        $named = $evaluated->get_named();
        for ($i = \count($positional); $i < \count($declared_arguments); $i++) {
            $argument = $declared_arguments[$i];
            if (isset($named[$argument->get_name()])) {
                $positional[] = $named[$argument->get_name()];
                unset($named[$argument->get_name()]);
            } else {
                assert($argument->get_default_value() !== null);
                $positional[] = $this->without_slash($argument->get_default_value()->accept($this), $argument->get_default_value());
            }
        }
        $argument_list = null;
        if ($overload->get_rest_argument() !== null) {
            $rest = array_values(array_splice($positional, \count($declared_arguments)));
            \assert(array_is_list($positional));
            $argument_list = new Sass_Argument_List($rest, $named, $evaluated->get_separator() === List_Separator::UNDECIDED ? List_Separator::COMMA : $evaluated->get_separator());
            $positional[] = $argument_list;
        }
        try {
            $result = $this->add_exception_span($node_with_span, fn() => $callback($positional));
        } catch (Sass_Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw $this->exception($e->get_message(), $node_with_span->get_span(), $e);
        }
        $this->callable_node = $old_callable_node;
        if ($argument_list === null) {
            return $result;
        }
        if ($named === []) {
            return $result;
        }
        if ($argument_list->were_keyword_accessed()) {
            return $result;
        }
        $unknown_names = array_keys($named);
        $last_name = array_pop($unknown_names);
        $message = sprintf('No argument%s named $%s%s.', $unknown_names ? 's' : '', $unknown_names ? implode(', $', $unknown_names) . ' or $' : '', $last_name);
        throw new Multi_Span_Sass_Runtime_Exception($message, $node_with_span->get_span(), 'invocation', ['declaration' => $overload->get_span_with_name()], $this->stack_trace($node_with_span->get_span()));
    }
    private function evaluate_arguments(Argument_Invocation $arguments): Argument_Results
    {
        $positional = [];
        $positional_nodes = [];
        foreach ($arguments->get_positional() as $expression) {
            $node_for_span = $this->expression_node($expression);
            $positional[] = $this->without_slash($expression->accept($this), $node_for_span);
            $positional_nodes[] = $node_for_span;
        }
        $named = [];
        $named_nodes = [];
        foreach ($arguments->get_named() as $key => $value) {
            $node_for_span = $this->expression_node($value);
            $named[$key] = $this->without_slash($value->accept($this), $node_for_span);
            $named_nodes[$key] = $node_for_span;
        }
        $rest_args = $arguments->get_rest();
        if ($rest_args === null) {
            return new Argument_Results($positional, $positional_nodes, $named, $named_nodes, List_Separator::UNDECIDED);
        }
        $rest = $rest_args->accept($this);
        $rest_node_for_span = $this->expression_node($rest_args);
        $separator = List_Separator::UNDECIDED;
        if ($rest instanceof Sass_Map) {
            $this->add_rest_map($named, $rest, $rest_args, fn($value): \Scss_Php\Scss_Php\Value\Value => $value);
            foreach ($rest->get_contents() as $key => $_) {
                assert($key instanceof Sass_String);
                $named_nodes[$key->get_text()] = $rest_node_for_span;
            }
        } elseif ($rest instanceof Sass_List) {
            foreach ($rest->as_list() as $value) {
                $positional[] = $this->without_slash($value, $rest_node_for_span);
                $positional_nodes[] = $rest_node_for_span;
                $separator = $rest->get_separator();
            }
            if ($rest instanceof Sass_Argument_List) {
                foreach ($rest->get_keywords() as $key => $value) {
                    $named[$key] = $this->without_slash($value, $rest_node_for_span);
                    $named_nodes[$key] = $rest_node_for_span;
                }
            }
        } else {
            $positional[] = $this->without_slash($rest, $rest_node_for_span);
            $positional_nodes[] = $rest_node_for_span;
        }
        $keyword_rest_args = $arguments->get_keyword_rest();
        if ($keyword_rest_args === null) {
            return new Argument_Results($positional, $positional_nodes, $named, $named_nodes, $separator);
        }
        $keyword_rest = $keyword_rest_args->accept($this);
        $keyword_rest_node_for_span = $this->expression_node($keyword_rest_args);
        if ($keyword_rest instanceof Sass_Map) {
            $this->add_rest_map($named, $keyword_rest, $keyword_rest_args, fn($value): \Scss_Php\Scss_Php\Value\Value => $value);
            foreach ($keyword_rest->get_contents() as $key => $_) {
                assert($key instanceof Sass_String);
                $named_nodes[$key->get_text()] = $keyword_rest_node_for_span;
            }
            return new Argument_Results($positional, $positional_nodes, $named, $named_nodes, $separator);
        }
        throw $this->exception("Variable keyword arguments must be a map (was {$keyword_rest}).", $keyword_rest_args->get_span());
    }
    /**
     * Evaluates the arguments in [arguments] only as much as necessary to
     * separate out positional and named arguments.
     *
     * Returns the arguments as expressions so that they can be lazily evaluated
     * for macros such as `if()`.
     *
     * @return array{list<Expression>, array<string, Expression>}
     */
    private function evaluate_macro_arguments(Callable_Invocation $invocation): array
    {
        $rest_args = $invocation->get_arguments()->get_rest();
        if ($rest_args === null) {
            return [$invocation->get_arguments()->get_positional(), $invocation->get_arguments()->get_named()];
        }
        $positional = $invocation->get_arguments()->get_positional();
        $named = $invocation->get_arguments()->get_named();
        $rest = $rest_args->accept($this);
        $rest_node_for_span = $this->expression_node($rest_args);
        if ($rest instanceof Sass_Map) {
            $this->add_rest_map($named, $rest, $rest_args, fn($value) => new Value_Expression($value, $rest_args->get_span()));
        } elseif ($rest instanceof Sass_List) {
            foreach ($rest->as_list() as $value) {
                $positional[] = new Value_Expression($this->without_slash($value, $rest_node_for_span), $rest_args->get_span());
            }
            if ($rest instanceof Sass_Argument_List) {
                foreach ($rest->get_keywords() as $key => $value) {
                    $named[$key] = new Value_Expression($this->without_slash($value, $rest_node_for_span), $rest_args->get_span());
                }
            }
        } else {
            $positional[] = new Value_Expression($this->without_slash($rest, $rest_node_for_span), $rest_args->get_span());
        }
        $keyword_rest_args = $invocation->get_arguments()->get_keyword_rest();
        if ($keyword_rest_args === null) {
            return [$positional, $named];
        }
        $keyword_rest = $keyword_rest_args->accept($this);
        $keyword_rest_node_for_span = $this->expression_node($keyword_rest_args);
        if ($keyword_rest instanceof Sass_Map) {
            $this->add_rest_map($named, $keyword_rest, $keyword_rest_args, fn($value) => new Value_Expression($this->without_slash($value, $keyword_rest_node_for_span), $keyword_rest_args->get_span()));
            return [$positional, $named];
        }
        throw $this->exception("Variable keyword arguments must be a map (was {$keyword_rest}).", $keyword_rest_args->get_span());
    }
    /**
     * Adds the values in $map to $values.
     *
     * Throws a {@see SassRuntimeException} associated with $nodeWithSpan's source
     * span if any $map keys aren't strings.
     *
     * @template T
     *
     * @param array<string, T>   $values
     * @param callable(Value): T $convert
     *
     * @param-immediately-invoked-callable $convert
     */
    private function add_rest_map(array &$values, Sass_Map $map, Ast_Node $node_with_span, callable $convert): void
    {
        $expression_node = $this->expression_node($node_with_span);
        foreach ($map->get_contents() as $key => $value) {
            if ($key instanceof Sass_String) {
                $values[$key->get_text()] = $convert($this->without_slash($value, $expression_node));
            } else {
                throw $this->exception("Variable keyword argument map must have string keys.\n{$key} is not a string in {$map}.", $node_with_span->get_span());
            }
        }
    }
    /**
     * @param array<string, mixed> $named
     *
     * @throws SassRuntimeException if $positional and $named aren't valid when applied to $arguments.
     */
    private function verify_arguments(int $positional, array $named, Argument_Declaration $arguments, Ast_Node $node_with_span): void
    {
        $this->add_exception_span($node_with_span, function () use ($positional, $named, $arguments): void {
            $arguments->verify($positional, $named);
        });
    }
    public function visit_selector_expression(Selector_Expression $node): Value
    {
        if ($this->style_rule_ignoring_at_root === null) {
            return Sass_Null::create();
        }
        return $this->style_rule_ignoring_at_root->get_original_selector()->as_sass_list();
    }
    public function visit_string_expression(String_Expression $node): Value
    {
        // Don't use [performInterpolation] here because we need to get the raw text
        // from strings, rather than the semantic value.
        $old_in_supports_declaration = $this->in_supports_declaration;
        $this->in_supports_declaration = false;
        $result = new Sass_String(implode('', array_map(function (\Scss_Php\Scss_Php\Ast\Sass\Expression|string $value): string {
            if (\is_string($value)) {
                return $value;
            }
            $expression = $value;
            $result = $expression->accept($this);
            if ($result instanceof Sass_String) {
                return $result->get_text();
            }
            return $this->serialize($result, $expression, false);
        }, $node->get_text()->get_contents())), $node->has_quotes());
        $this->in_supports_declaration = $old_in_supports_declaration;
        return $result;
    }
    public function visit_supports_expression(Supports_Expression $node): Value
    {
        return new Sass_String($this->visit_supports_condition($node->get_condition()), false);
    }
    /**
     * Runs $callback for each value in $list until it returns a {@see Value}.
     *
     * Returns the value returned by $callback, or `null` if it only ever
     * returned `null`.
     *
     * @template T
     *
     * @param T[]                 $list
     * @param callable(T): ?Value $callback
     *
     * @param-immediately-invoked-callable $callback
     */
    private function handle_return(array $list, callable $callback): ?Value
    {
        foreach ($list as $value) {
            $result = $callback($value);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }
    /**
     * Runs $callback with $environment as the current environment.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     * @param-immediately-invoked-callable $callback
     */
    private function with_environment(Environment $environment, callable $callback)
    {
        $old_environment = $this->environment;
        $this->environment = $environment;
        $result = $callback();
        $this->environment = $old_environment;
        return $result;
    }
    /**
     * @return CssValue<string>
     */
    private function interpolation_to_value(Interpolation $interpolation, bool $warn_for_color = false, bool $trim = false): Css_Value
    {
        $result = $this->perform_interpolation($interpolation, $warn_for_color);
        return new Css_Value($trim ? String_Util::trim_ascii($result, true) : $result, $interpolation->get_span());
    }
    /**
     * Evaluates $interpolation.
     *
     * If $warnForColor is `true`, this will emit a warning for any named color
     * values passed into the interpolation.
     */
    private function perform_interpolation(Interpolation $interpolation, bool $warn_for_color = false): string
    {
        $tuple = $this->perform_interpolation_helper($interpolation, false, $warn_for_color);
        return $tuple[0];
    }
    /**
     * Like {@see performInterpolation}, but also returns a {@see InterpolationMap} that
     * can map spans from the resulting string back to the original
     * $interpolation.
     *
     * @return array{string, InterpolationMap}
     */
    private function perform_interpolation_with_map(Interpolation $interpolation, bool $warn_for_color = false): array
    {
        $tuple = $this->perform_interpolation_helper($interpolation, true, $warn_for_color);
        \assert($tuple[1] !== null);
        return $tuple;
    }
    /**
     * A helper that implements the core logic of both {@see performInterpolation}
     * and {@see performInterpolationWithMap}.
     *
     * @return array{string, InterpolationMap|null}
     */
    private function perform_interpolation_helper(Interpolation $interpolation, bool $source_map, bool $warn_for_color = false): array
    {
        $target_locations = $source_map ? [] : null;
        $old_in_supports_declaration = $this->in_supports_declaration;
        $this->in_supports_declaration = false;
        $buffer = '';
        $first = true;
        foreach ($interpolation->get_contents() as $value) {
            if (!$first && $target_locations !== null) {
                $target_locations[] = new Simple_Source_Location(\strlen($buffer));
            }
            $first = false;
            if (\is_string($value)) {
                $buffer .= $value;
                continue;
            }
            $expression = $value;
            $result = $expression->accept($this);
            if ($warn_for_color && $result instanceof Sass_Color && null !== $color_name = Colors::rg_ba_to_color_name($result->get_red(), $result->get_green(), $result->get_blue(), $result->get_alpha())) {
                $alternative = new Binary_Operation_Expression(Binary_Operator::PLUS, new String_Expression(new Interpolation([''], $interpolation->get_span()), true), $expression);
                $this->warn("You probably don't mean to use the color value {$color_name} in interpolation here.\nIt may end up represented as {$result}, which will likely produce invalid CSS.\nAlways quote color names when using them as strings or map keys (for example, \"{$color_name}\").\nIf you really want to use the color value here, use '{$alternative}'.", $expression->get_span());
            }
            $buffer .= $this->serialize($result, $expression, false);
        }
        $this->in_supports_declaration = $old_in_supports_declaration;
        return [$buffer, $target_locations === null ? null : new Interpolation_Map($interpolation, $target_locations)];
    }
    /**
     * Evaluates $expression and calls `toCssString()` and wraps a
     * {@see SassScriptException} to associate it with its span.
     */
    private function evaluate_to_css(Expression $expression, bool $quote = true): string
    {
        return $this->serialize($expression->accept($this), $expression, $quote);
    }
    /**
     * Calls `value->toCssString()` and wraps a {@see SassScriptException} to associate
     * it with $nodeWithSpan's source span.
     *
     * This takes an {@see AstNode} rather than a {@see FileSpan} so it can avoid calling
     * {@see AstNode::getSpan} if the span isn't required, since some nodes need to do
     * real work to manufacture a source span.
     */
    private function serialize(Value $value, Ast_Node $node_with_span, bool $quote = true): string
    {
        return $this->add_exception_span($node_with_span, fn() => $value->to_css_string($quote));
    }
    /**
     * Runs $callback with $rule as the current style rule.
     *
     * @template T
     *
     * @param callable(): T          $callback
     *
     * @return T
     * @param-immediately-invoked-callable $callback
     */
    private function with_style_rule(Modifiable_Css_Style_Rule $rule, callable $callback)
    {
        $old_rule = $this->style_rule_ignoring_at_root;
        $this->style_rule_ignoring_at_root = $rule;
        $result = $callback();
        $this->style_rule_ignoring_at_root = $old_rule;
        return $result;
    }
    /**
     * Runs $callback with $queries as the current media queries.
     *
     * This also sets $sources as the current set of media queries that were
     * merged together to create $queries. This is used to determine when it's
     * safe to bubble one query through another.
     *
     * @template T
     *
     * @param list<CssMediaQuery>|null $queries
     * @param CssMediaQuery[]|null     $sources
     * @param callable(): T            $callback
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    private function with_media_queries(?array $queries, ?array $sources, callable $callback)
    {
        $old_media_queries = $this->media_queries;
        $old_sources = $this->media_query_sources;
        $this->media_queries = $queries;
        $this->media_query_sources = $sources;
        $result = $callback();
        $this->media_queries = $old_media_queries;
        $this->media_query_sources = $old_sources;
        return $result;
    }
    /**
     * Returns the {@see AstNode} whose span should be used for $expression.
     *
     * If $expression is a variable reference, {@see AstNode}'s span will be the span
     * where that variable was originally declared. Otherwise, this will just
     * return $expression.
     */
    private function expression_node(Ast_Node $expression): Ast_Node
    {
        if ($expression instanceof Variable_Expression) {
            return $this->add_exception_span($expression, fn() => $this->environment->get_variable_node($expression->get_name()) ?? $expression);
        }
        return $expression;
    }
    /**
     * Adds $node as a child of the current parent, then runs $callback with
     * $node as the current parent.
     *
     * If $through is passed, $node is added as a child of the first parent for
     * which $through returns `false`. That parent is copied unless it's the
     * lattermost child of its parent.
     *
     * Runs $callback in a new environment scope unless $scopeWhen is false.
     *
     * @template S of ModifiableCssParentNode
     * @template T
     *
     * @param S                            $node
     * @param callable(): T                $callback
     * @param null|callable(CssNode): bool $through
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     * @param-immediately-invoked-callable $through
     */
    private function with_parent(Modifiable_Css_Parent_Node $node, callable $callback, ?callable $through = null, bool $scope_when = true)
    {
        $this->add_child($node, $through);
        $old_parent = $this->parent;
        $this->parent = $node;
        $result = $this->environment->scope($callback, $scope_when);
        $this->parent = $old_parent;
        return $result;
    }
    /**
     * Adds $node as a child of the current parent.
     *
     * If $through is passed, $node is added as a child of the first parent for
     * which $through returns `false` instead. That parent is copied unless it's the
     * lattermost child of its parent.
     *
     * @param null|callable(CssNode): bool $through
     *
     * @param-immediately-invoked-callable $through
     */
    private function add_child(Modifiable_Css_Node $node, ?callable $through = null): void
    {
        // Go up through parents that match [through].
        $parent = $this->get_parent();
        if ($through !== null) {
            while ($through($parent)) {
                $grand_parent = $parent->get_parent();
                if ($grand_parent === null) {
                    throw new \InvalidArgumentException('$through() must return false for at least one parent of the node.');
                }
                $parent = $grand_parent;
            }
        }
        // If the parent has a (visible) following sibling, we shouldn't add to
        // the parent. Instead, we should create a copy and add it after the
        // interstitial sibling.
        if ($parent->has_following_sibling()) {
            $grand_parent = $parent->get_parent();
            // A node with siblings must have a parent
            assert($grand_parent !== null);
            $last_child = List_Util::last($grand_parent->get_children());
            if ($parent->equals_ignoring_children($last_child)) {
                \assert($last_child instanceof Modifiable_Css_Parent_Node);
                $parent = $last_child;
            } else {
                $parent = $parent->copy_without_children();
                $grand_parent->add_child($parent);
            }
        }
        $parent->add_child($node);
    }
    /**
     * Adds a frame to the stack with the given $member name, and $nodeWithSpan
     * as the site of the new frame.
     *
     * Runs $callback with the new stack.
     *
     * This takes an {@see AstNode} rather than a {@see FileSpan} so it can avoid calling
     * {@see AstNode::getSpan} if the span isn't required, since some nodes need to do
     * real work to manufacture a source span.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    private function with_stack_frame(string $member, Ast_Node $node_with_span, callable $callback)
    {
        $this->stack[] = [$this->member, $node_with_span];
        $old_member = $this->member;
        $this->member = $member;
        $result = $callback();
        $this->member = $old_member;
        array_pop($this->stack);
        return $result;
    }
    /**
     * Like {@see Value::withoutSlash}, but produces a deprecation warning if $value
     * was a slash-separated number.
     */
    private function without_slash(Value $value, Ast_Node $node_for_span): Value
    {
        if ($value instanceof Sass_Number && $value->get_as_slash() !== null) {
            $recommendation = function (Sass_Number $number) use (&$recommendation): string {
                if ($number->get_as_slash() !== null) {
                    [$before, $after] = $number->get_as_slash();
                    return "math.div({$recommendation($before)}, {$recommendation($after)})";
                }
                return (string) $number;
            };
            $message = <<<WARNING
            Using / for division is deprecated and will be removed in Dart Sass 2.0.0.
            
            Recommendation: {$recommendation($value)}
            
            More info and automated migrator: https://sass-lang.com/d/slash-div
            WARNING;
            $this->warn($message, $node_for_span->get_span(), Deprecation::slashDiv);
        }
        return $value->without_slash();
    }
    /**
     * Creates a new stack frame with location information from $member$ and
     * $span.
     */
    private function stack_frame(string $member, File_Span $span): Frame
    {
        $url = $span->get_source_url();
        if ($url !== null) {
            $url = $this->import_cache->humanize($url);
        }
        return Util::frame_for_span($span, $member, $url);
    }
    /**
     * Returns a stack trace at the current point.
     *
     * If $span is passed, it's used for the innermost stack frame.
     */
    private function stack_trace(?File_Span $span = null): Trace
    {
        $frames = [];
        foreach ($this->stack as [$member, $node_with_span]) {
            $frames[] = $this->stack_frame($member, $node_with_span->get_span());
        }
        if ($span !== null) {
            $frames[] = $this->stack_frame($this->member, $span);
        }
        return new Trace(array_reverse($frames));
    }
    public function warn(string $message, File_Span $span, ?Deprecation $deprecation = null): void
    {
        if ($this->quiet_deps && ($this->in_dependency || $this->current_callable !== null && $this->current_callable->is_in_dependency())) {
            return;
        }
        $span_string = ($span->get_source_url() ?? '') . "\x00" . $span->get_start()->get_offset() . "\x00" . $span->get_end()->get_offset();
        if (isset($this->warnings_emitted[$message][$span_string])) {
            return;
        }
        $this->warnings_emitted[$message][$span_string] = true;
        $trace = $this->stack_trace($span);
        if ($deprecation === null) {
            $this->logger->warn($message, null, $span, $trace);
        } else {
            Logger_Util::warn_for_deprecation($this->logger, $deprecation, $message, $span, $trace);
        }
    }
    /**
     * Returns a {@see SassRuntimeException} with the given $message.
     *
     * If $span is passed, it's used for the innermost stack frame.
     */
    private function exception(string $message, ?File_Span $span = null, ?\Throwable $previous = null): Sass_Runtime_Exception
    {
        return new Simple_Sass_Runtime_Exception($message, $span ?? List_Util::last($this->stack)[1]->get_span(), $this->stack_trace($span), $previous);
    }
    /**
     * Returns a {@see MultiSpanSassRuntimeException} with the given $message,
     * $primaryLabel, and $secondaryLabels.
     *
     * The primary span is taken from the current stack trace span.
     *
     * @param array<string, FileSpan> $secondarySpans
     */
    private function multi_span_exception(string $message, string $primary_label, array $secondary_spans): Sass_Runtime_Exception
    {
        return new Multi_Span_Sass_Runtime_Exception($message, List_Util::last($this->stack)[1]->get_span(), $primary_label, $secondary_spans, $this->stack_trace());
    }
    /**
     * Runs $callback, and converts any {@see SassScriptException}s it throws to
     * {@see SassRuntimeException}s with $nodeWithSpan's source span.
     *
     * This takes an {@see AstNode} rather than a {@see FileSpan} so it can avoid calling
     * {@see AstNode::getSpan} if the span isn't required, since some nodes need to do
     * real work to manufacture a source span.
     *
     * If $addStackFrame is true (the default), this will add an innermost stack
     * frame for $nodeWithSpan. Otherwise, it will use the existing stack as-is.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws SassRuntimeException
     *
     * @param-immediately-invoked-callable $callback
     */
    private function add_exception_span(Ast_Node $node_with_span, callable $callback, bool $add_stack_frame = true)
    {
        try {
            return $callback();
        } catch (Sass_Script_Exception $e) {
            throw $e->with_span($node_with_span->get_span())->with_trace($this->stack_trace($add_stack_frame ? $node_with_span->get_span() : null), $e);
        }
    }
    /**
     * Runs $callback, and converts any {@see SassException}s that aren't already
     * {@see SassRuntimeException}s to {@see SassRuntimeException}s with the current stack
     * trace.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     *
     * @param-immediately-invoked-callable $callback
     */
    private function add_exception_trace(callable $callback)
    {
        try {
            return $callback();
        } catch (Sass_Runtime_Exception $e) {
            throw $e;
        } catch (Sass_Exception $e) {
            throw $e->with_trace($this->stack_trace($e->get_span()), $e);
        }
    }
    /**
     * Runs $callback, and converts any {@see SassRuntimeException}s containing an
     * `@error` to throw a more relevant {@see SassRuntimeException}s with $nodeWithSpan's
     * source span.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     * @param-immediately-invoked-callable $callback
     */
    private function add_error_span(Ast_Node $node_with_span, callable $callback)
    {
        try {
            return $callback();
        } catch (Sass_Runtime_Exception $e) {
            if (!str_starts_with($e->get_span()->get_text(), '@error')) {
                throw $e;
            }
            throw new Simple_Sass_Runtime_Exception($e->get_original_message(), $node_with_span->get_span(), $this->stack_trace(), $e);
        }
    }
}