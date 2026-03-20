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
namespace Scss_Php\Scss_Php;

use League\Uri\Contracts\Uri_Interface;
use League\Uri\Uri;
use Scss_Php\Scss_Php\Ast\Css\Css_Parent_Node;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Collection\Map;
use Scss_Php\Scss_Php\Compiler\Legacy_Value_Visitor;
use Scss_Php\Scss_Php\Evaluation\Evaluate_Visitor;
use Scss_Php\Scss_Php\Exception\Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Function\Function_Registry;
use Scss_Php\Scss_Php\Importer\Filesystem_Importer;
use Scss_Php\Scss_Php\Importer\Import_Cache;
use Scss_Php\Scss_Php\Importer\Importer;
use Scss_Php\Scss_Php\Importer\Legacy_Callback_Importer;
use Scss_Php\Scss_Php\Importer\No_Op_Importer;
use Scss_Php\Scss_Php\Logger\Deprecation_Processing_Logger;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Logger\Stream_Logger;
use Scss_Php\Scss_Php\Node\Number;
use Scss_Php\Scss_Php\Sass_Callable\Built_In_Callable;
use Scss_Php\Scss_Php\Serializer\Serializer;
use Scss_Php\Scss_Php\Util\Path;
use Scss_Php\Scss_Php\Value\List_Separator;
use Scss_Php\Scss_Php\Value\Sass_Argument_List;
use Scss_Php\Scss_Php\Value\Sass_Boolean;
use Scss_Php\Scss_Php\Value\Sass_Color;
use Scss_Php\Scss_Php\Value\Sass_List;
use Scss_Php\Scss_Php\Value\Sass_Map;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Sass_Number;
use Scss_Php\Scss_Php\Value\Sass_String;
use Scss_Php\Scss_Php\Value\Value;
use Scss_Php\Scss_Php\Visitor\Css_Visitor;
final class Compiler
{
    public const SOURCE_MAP_NONE = 0;
    public const SOURCE_MAP_INLINE = 1;
    public const SOURCE_MAP_FILE = 2;
    public static $true = [Type::T_KEYWORD, 'true'];
    public static $false = [Type::T_KEYWORD, 'false'];
    public static $null = [Type::T_NULL];
    public static $empty_list = [Type::T_LIST, '', []];
    public static $empty_map = [Type::T_MAP, [], []];
    public static $empty_string = [Type::T_STRING, '"', []];
    /**
     * @var list<Importer>
     */
    private array $importers = [];
    /**
     * @var array<int, string|callable(string): (string|null)>
     */
    private array $import_paths = [];
    /**
     * @var array<string, array{0: callable, 1: string[]}>
     */
    private array $user_functions = [];
    /**
     * @var array<string, Value>
     */
    private array $registered_vars = [];
    /**
     * @var self::SOURCE_MAP_*
     */
    private int $source_map = self::SOURCE_MAP_NONE;
    /**
     * @var array{sourceRoot?: string, sourceMapFilename?: string|null, sourceMapURL?: string|null, outputSourceFiles?: bool, sourceMapRootpath?: string, sourceMapBasepath?: string}
     */
    private array $source_map_options = [];
    private bool $charset = true;
    private bool $quiet_deps = false;
    /**
     * Deprecation warnings of these types will be ignored.
     *
     * @var Deprecation[]
     */
    private array $silence_deprecations = [];
    /**
     * Deprecation warnings of one of these types will cause an error to be
     * thrown.
     *
     * Future deprecations in this list will still cause an error even if they
     * are not also in {@see $futureDeprecations}.
     *
     * @var Deprecation[]
     */
    private array $fatal_deprecations = [];
    /**
     * Future deprecations that the user has explicitly opted into.
     *
     * @var Deprecation[]
     */
    private array $future_deprecations = [];
    private bool $verbose = false;
    private Output_Style $output_style = Output_Style::EXPANDED;
    private Logger_Interface $logger;
    public function __construct()
    {
        $this->logger = new Stream_Logger(fopen('php://stderr', 'w'), true);
    }
    /**
     * Sets an alternative logger.
     *
     * Changing the logger in the middle of the compilation is not
     * supported and will result in an undefined behavior.
     */
    public function set_logger(Logger_Interface $logger): void
    {
        $this->logger = $logger;
    }
    /**
     * Replaces variables.
     *
     * @param array<string, Value> $variables
     */
    public function replace_variables(array $variables): void
    {
        $this->registered_vars = [];
        $this->add_variables($variables);
    }
    /**
     * Replaces variables.
     *
     * @param array<string, Value> $variables
     */
    public function add_variables(array $variables): void
    {
        foreach ($variables as $name => $value) {
            if (!$value instanceof Value) {
                throw new \InvalidArgumentException('Passing raw values to as custom variables to the Compiler is not supported anymore. Use "\ScssPhp\ScssPhp\ValueConverter::parseValue" or "\ScssPhp\ScssPhp\ValueConverter::fromPhp" to convert them instead.');
            }
            $this->registered_vars[$name] = $value;
        }
    }
    /**
     * Unset variable
     */
    public function unset_variable(string $name): void
    {
        unset($this->registered_vars[$name]);
    }
    /**
     * Returns list of variables
     *
     * @return array<string, Value>
     */
    public function get_variables(): array
    {
        return $this->registered_vars;
    }
    public function add_importer(Importer $importer): void
    {
        $this->importers[] = $importer;
    }
    /**
     * Add import path
     *
     * @param string|callable(string): (string|null) $path
     */
    public function add_import_path(string|callable $path): void
    {
        if (!\in_array($path, $this->import_paths)) {
            $this->import_paths[] = $path;
        }
    }
    /**
     * Set import paths
     *
     * @param string|array<string|callable(string): (string|null)> $path
     */
    public function set_import_paths($path): void
    {
        $paths = (array) $path;
        $actual_import_paths = array_filter($paths, fn(callable|string $path) => $path !== '');
        if (\count($actual_import_paths) !== \count($paths)) {
            throw new \InvalidArgumentException('Passing an empty string in the import paths to refer to the current working directory is not supported anymore. If that\'s the intended behavior, the value of "getcwd()" should be used directly instead. If this was used for resolving relative imports of the input alongside "chdir" with the source directory, the path of the input file should be passed to "compileString()" instead.');
        }
        $this->import_paths = $actual_import_paths;
    }
    /**
     * Sets the output style.
     */
    public function set_output_style(Output_Style $style): void
    {
        $this->output_style = $style;
    }
    /**
     * Configures the handling of non-ASCII outputs.
     *
     * If $charset is `true`, this will include a `@charset` declaration or a
     * UTF-8 [byte-order mark][] if the stylesheet contains any non-ASCII
     * characters. Otherwise, it will never include a `@charset` declaration or a
     * byte-order mark.
     *
     * [byte-order mark]: https://en.wikipedia.org/wiki/Byte_order_mark#UTF-8
     */
    public function set_charset(bool $charset): void
    {
        $this->charset = $charset;
    }
    /**
     * If set to `true`, this will silence compiler warnings emitted for stylesheets loaded through {@see $importers} or {@see $importPaths}
     */
    public function set_quiet_deps(bool $quiet_deps): void
    {
        $this->quiet_deps = $quiet_deps;
    }
    /**
     * Configures the deprecation warning types that will be ignored.
     *
     * @param Deprecation[] $silenceDeprecations
     */
    public function set_silence_deprecations(array $silence_deprecations): void
    {
        $this->silence_deprecations = $silence_deprecations;
    }
    /**
     * Configures the deprecation warning types that will cause an error to be thrown.
     *
     * @param Deprecation[] $fatalDeprecations
     */
    public function set_fatal_deprecations(array $fatal_deprecations): void
    {
        $this->fatal_deprecations = $fatal_deprecations;
    }
    /**
     * Configures the opt-in for future deprecation warning types.
     *
     * @param Deprecation[] $futureDeprecations
     */
    public function set_future_deprecations(array $future_deprecations): void
    {
        $this->future_deprecations = $future_deprecations;
    }
    /**
     * Configures the verbosity of deprecation warnings.
     *
     * In non-verbose mode, repeated deprecations are hidden once reaching the
     * threshold, with a summary at the end. In verbose mode, all deprecation
     * warnings are emitted to the logger.
     */
    public function set_verbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }
    /**
     * Enable/disable source maps
     *
     * @param self::SOURCE_MAP_* $sourceMap
     */
    public function set_source_map(int $source_map): void
    {
        $this->source_map = $source_map;
    }
    /**
     * Set source map options
     *
     * @param array{sourceRoot?: string, sourceMapFilename?: string|null, sourceMapURL?: string|null, outputSourceFiles?: bool, sourceMapRootpath?: string, sourceMapBasepath?: string} $sourceMapOptions
     */
    public function set_source_map_options(array $source_map_options): void
    {
        $this->source_map_options = $source_map_options;
    }
    /**
     * Registers a custom function
     *
     * @param (callable(list<Value>): Value)|(callable(list<array|Number>): (array|Number)) $callback
     * @param string[] $argumentDeclaration
     */
    public function register_function(string $name, callable $callback, array $argument_declaration): void
    {
        $normalized_name = $this->normalize_name($name);
        if (Function_Registry::is_builtin_function($normalized_name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" function is a core sass function. Overriding it with a custom implementation through "%s" is not supported .', $name, __METHOD__));
        }
        $this->user_functions[$normalized_name] = [$callback, $argument_declaration];
    }
    /**
     * Unregisters a custom function
     */
    public function unregister_function(string $name): void
    {
        unset($this->user_functions[$this->normalize_name($name)]);
    }
    private function normalize_name(string $name): string
    {
        return str_replace('-', '_', $name);
    }
    /**
     * Compiles the provided scss file into CSS.
     *
     * Imports are resolved by trying, in order:
     *
     * * Loading a file relative to $path.
     *
     * * Each importer in {@see $importers}.
     *
     * * Each load path in {@see $importPaths}. Note that this is a shorthand for adding
     *   {@see FilesystemImporter}s to {@see $importers}.
     *
     * @throws SassException when the source fails to compile
     */
    public function compile_file(string $path): Compilation_Result
    {
        // Force loading the CssParentNode and CssVisitor before using the AST classes because of a weird PHP behavior.
        class_exists(Css_Parent_Node::class);
        class_exists(Css_Visitor::class);
        $logger = new Deprecation_Processing_Logger($this->logger, $this->silence_deprecations, $this->fatal_deprecations, $this->future_deprecations, !$this->verbose);
        $logger->validate();
        $import_cache = $this->create_import_cache($logger);
        $importer = new Filesystem_Importer(null);
        $stylesheet = $import_cache->import_canonical($importer, Path::to_uri(Path::canonicalize($path)), Path::to_uri($path));
        \assert($stylesheet !== null, 'The filesystem importer never returns null when loading a canonical URL. It either succeeds or throws an error.');
        $result = $this->compile_stylesheet($stylesheet, $import_cache, $logger, $importer);
        $logger->summarize();
        return $result;
    }
    /**
     * Compiles the provided scss source code into CSS.
     *
     * Imports are resolved by trying, in order:
     *
     * * The given $importer, with the imported URL resolved relative to $url.
     *
     * * Each importer in {@see $importers}.
     *
     * * Each load path in {@see $importPaths}. Note that this is a shorthand for adding
     *   {@see FilesystemImporter}s to {@see $importers}.
     *
     * The $url indicates the location from which $source was loaded. If $importer is
     * passed, $url must be passed as well and `$importer->load($url)` should
     * return `$source`.
     *
     * @throws SassException when the source fails to compile
     */
    public function compile_string(string $source, Uri_Interface|string|null $url = null, ?Importer $importer = null, Syntax $syntax = Syntax::SCSS): Compilation_Result
    {
        // Force loading the CssParentNode and CssVisitor before using the AST classes because of a weird PHP behavior.
        class_exists(Css_Parent_Node::class);
        class_exists(Css_Visitor::class);
        $logger = new Deprecation_Processing_Logger($this->logger, $this->silence_deprecations, $this->fatal_deprecations, $this->future_deprecations, !$this->verbose);
        $logger->validate();
        if (\is_string($url)) {
            @trigger_error('Passing a path to "Compiler::compileString" is deprecated. Use `Compiler::compileFile" or pass a "UriInterface" instead.', E_USER_DEPRECATED);
            $url = Path::to_uri($url);
            $importer ??= new Filesystem_Importer(null);
        }
        $import_cache = $this->create_import_cache($logger);
        $stylesheet = Stylesheet::parse($source, $syntax, $logger, $url);
        $importer ??= $url === null ? new No_Op_Importer() : new Filesystem_Importer(null);
        $result = $this->compile_stylesheet($stylesheet, $import_cache, $logger, $importer);
        $logger->summarize();
        return $result;
    }
    private function create_import_cache(Logger_Interface $logger): Import_Cache
    {
        $importers = $this->importers;
        foreach ($this->import_paths as $import_path) {
            if (\is_string($import_path)) {
                $importers[] = new Filesystem_Importer($import_path);
            } elseif (is_callable($import_path)) {
                $importers[] = new Legacy_Callback_Importer($import_path(...));
                // TODO report deprecation
            }
        }
        return new Import_Cache($importers, $logger);
    }
    /**
     * @throws SassException
     */
    private function compile_stylesheet(Stylesheet $stylesheet, Import_Cache $import_cache, Logger_Interface $logger, Importer $importer): Compilation_Result
    {
        $wants_source_map = $this->source_map !== self::SOURCE_MAP_NONE;
        $functions = [];
        foreach ($this->user_functions as $name => $user_function) {
            $ref = new \ReflectionFunction($user_function[0](...));
            $signature = implode(', ', array_map(fn(string $arg): string => '$' . $arg, $user_function[1]));
            if ($ref->has_return_type() && $ref->get_return_type() instanceof \ReflectionNamedType && $ref->get_return_type()->get_name() === Value::class) {
                $callback = $user_function[0];
            } else {
                $legacy_callback = $user_function[0];
                $callback = function (array $arguments) use ($legacy_callback): Value {
                    $args = [];
                    foreach ($arguments as $argument) {
                        $args[] = $this->value_to_legacy_value($argument);
                    }
                    $result = $legacy_callback($args);
                    if ($result instanceof Value) {
                        return $result;
                    }
                    return $this->legacy_value_to_value($result);
                };
            }
            $functions[] = Built_In_Callable::function($name, $signature, $callback);
        }
        $initial_variables = [];
        foreach ($this->registered_vars as $variable_name => $variable) {
            if ($variable_name[0] === '$') {
                $variable_name = substr($variable_name, 1);
            }
            $variable_name = str_replace('_', '-', $variable_name);
            $initial_variables[$variable_name] = $variable;
        }
        $evaluate_result = (new Evaluate_Visitor($import_cache, $functions, $logger, $this->quiet_deps, sourceMap: $wants_source_map))->run($importer, $stylesheet, $initial_variables);
        $serialize_result = Serializer::serialize($evaluate_result->get_stylesheet(), style: $this->output_style, sourceMap: $wants_source_map, charset: $this->charset, logger: $logger);
        $css = $serialize_result->css;
        $source_map = null;
        if ($serialize_result->mapping !== null) {
            $mapping = $serialize_result->mapping;
            if (isset($this->source_map_options['sourceMapBasepath']) || isset($this->source_map_options['sourceMapRootpath'])) {
                $mapping = $mapping->map_urls(function (string $url) {
                    $uri = Uri::new($url);
                    if ($uri->get_scheme() !== null && $uri->get_scheme() !== 'file') {
                        return $uri->to_string();
                    }
                    $path = Path::from_uri($uri);
                    if (isset($this->source_map_options['sourceMapBasepath']) && $this->source_map_options['sourceMapBasepath'] !== '') {
                        $path = Path::relative($path, $this->source_map_options['sourceMapBasepath']);
                    }
                    return Path::normalize(Path::join($this->source_map_options['sourceMapRootpath'] ?? '', $path));
                });
            }
            if (isset($this->source_map_options['sourceMapFilename'])) {
                $mapping->target_url = $this->source_map_options['sourceMapFilename'];
            }
            if (isset($this->source_map_options['sourceRoot'])) {
                $mapping->source_root = $this->source_map_options['sourceRoot'];
            }
            $source_map = json_encode($mapping->to_json($this->source_map_options['outputSourceFiles'] ?? false), \JSON_THROW_ON_ERROR);
            $source_map_url = null;
            switch ($this->source_map) {
                case self::SOURCE_MAP_INLINE:
                    $source_map_url = 'data:application/json;charset=utf-8,' . Util::encode_uri_component($source_map);
                    break;
                case self::SOURCE_MAP_FILE:
                    if (isset($this->source_map_options['sourceMapURL'])) {
                        $source_map_url = $this->source_map_options['sourceMapURL'];
                    }
                    break;
            }
            if ($source_map_url !== null) {
                $escaped_url = str_replace('*/', '%2A/', $source_map_url);
                $css .= ($this->output_style === Output_Style::COMPRESSED ? '' : "\n\n") . "/*# sourceMappingURL={$escaped_url} */";
            }
        }
        return new Compilation_Result($css, $source_map, $evaluate_result->get_loaded_urls());
    }
    /**
     * Converts a Sass value to its legacy representation.
     *
     * @return array|Number
     */
    private function value_to_legacy_value(Value $value)
    {
        $visitor = new Legacy_Value_Visitor();
        return $value->accept($visitor);
    }
    /**
     * Converts a legacy Sass value to its modern representation.
     *
     * @param array|Number $legacyValue
     */
    private function legacy_value_to_value(array $legacy_value): Value
    {
        if ($legacy_value instanceof Number) {
            return Sass_Number::with_units($legacy_value->get_dimension(), $legacy_value->get_numerator_units(), $legacy_value->get_denominator_units());
        }
        switch ($legacy_value[0]) {
            case Type::T_KEYWORD:
                if ($legacy_value === self::$true || $legacy_value === self::$false) {
                    return Sass_Boolean::create($legacy_value === self::$true);
                }
                throw new \UnexpectedValueException('Unsupported value using the "keyword" type. Only boolean values should use it as their representation.');
            case Type::T_COLOR:
                return Sass_Color::rgb($legacy_value[1], $legacy_value[2], $legacy_value[3], $legacy_value[4] ?? 1.0);
            case Type::T_STRING:
                return new Sass_String($this->get_string_text($legacy_value), $legacy_value[1] !== '');
            case Type::T_LIST:
                $items = [];
                foreach ($legacy_value[2] as $item) {
                    $items[] = $this->legacy_value_to_value($item);
                }
                $separator = match ($legacy_value[1]) {
                    ',' => List_Separator::COMMA,
                    ' ' => List_Separator::SPACE,
                    '/' => List_Separator::SLASH,
                    '' => List_Separator::UNDECIDED,
                    default => throw new \LogicException(\sprintf('Unsupported list separator "%s".', $legacy_value[1])),
                };
                if (isset($legacy_value[3]) && \is_array($legacy_value[3])) {
                    $keywords = [];
                    foreach ($legacy_value[3] as $name => $item) {
                        assert(\is_string($name));
                        $keywords[$name] = $this->legacy_value_to_value($item);
                    }
                    return new Sass_Argument_List($items, $keywords, $separator);
                }
                $has_brackets = ($legacy_value['enclosing'] ?? null) === 'bracket';
                return new Sass_List($items, $separator, $has_brackets);
            case Type::T_MAP:
                $map = new Map();
                $keys = $legacy_value[1];
                $values = $legacy_value[2];
                for ($i = 0, $s = \count($keys); $i < $s; $i++) {
                    $map->put($this->legacy_value_to_value($keys[$i]), $this->legacy_value_to_value($values[$i]));
                }
                return Sass_Map::create($map);
            case Type::T_NULL:
                return Sass_Null::create();
            default:
                throw new \UnexpectedValueException(sprintf('"Unsupported type "%s" for the value conversion.', $legacy_value[0]));
        }
    }
    /**
     * Detects whether the import is a CSS import.
     */
    public static function is_css_import(string $url): bool
    {
        return 1 === preg_match('~\.css$|^https?://|^//~', $url);
    }
    /**
     * Is truthy?
     *
     * @param array|Number $value
     */
    public function is_truthy($value): bool
    {
        return $value !== self::$false && $value !== self::$null;
    }
    /**
     * Cast to Sass boolean
     */
    public function to_bool(bool $thing): array
    {
        return $thing ? self::$true : self::$false;
    }
    /**
     * Gets the text of a Sass string
     *
     * Calling this method on anything else than a SassString is unsupported. Use {@see assertString} first
     * to ensure that the value is indeed a string.
     */
    public function get_string_text(array $value): string
    {
        if ($value[0] !== Type::T_STRING) {
            throw new \InvalidArgumentException('The argument is not a sass string. Did you forgot to use "assertString"?');
        }
        return $this->compile_string_content($value);
    }
    /**
     * Compile string content
     */
    private function compile_string_content(array $string): string
    {
        $parts = [];
        foreach ($string[2] as $part) {
            if (\is_array($part) || $part instanceof Number) {
                $parts[] = $this->compile_value($part);
            } else {
                $parts[] = $part;
            }
        }
        return implode('', $parts);
    }
    /**
     * Assert value is a string
     *
     * This method deals with internal implementation details of the value
     * representation where unquoted strings can sometimes be stored under
     * other types.
     * The returned value is always using the T_STRING type.
     *
     * @param array|Number $value
     *
     * @throws SassScriptException
     */
    public function assert_string($value, ?string $var_name = null): array
    {
        if ($value[0] === Type::T_STRING) {
            assert(\is_array($value));
            return $value;
        }
        $value = $this->compile_value($value);
        throw Sass_Script_Exception::for_argument("{$value} is not a string.", $var_name);
    }
    /**
     * Assert value is a map
     *
     * @param array|Number $value
     *
     * @throws SassScriptException
     */
    public function assert_map($value, ?string $var_name = null): array
    {
        $map = $this->try_map($value);
        if ($map === null) {
            $value = $this->compile_value($value);
            throw Sass_Script_Exception::for_argument("{$value} is not a map.", $var_name);
        }
        return $map;
    }
    /**
     * Tries to convert an item to a Sass map
     *
     * @param Number|array $item
     */
    private function try_map(array $item): ?array
    {
        if ($item instanceof Number) {
            return null;
        }
        if ($item[0] === Type::T_MAP) {
            return $item;
        }
        if ($item[0] === Type::T_LIST && $item[2] === []) {
            return self::$empty_map;
        }
        return null;
    }
    /**
     * Gets the keywords of an argument list.
     *
     * Keys in the returned array are normalized names (underscores are replaced with dashes)
     * without the leading `$`.
     * Calling this helper with anything that an argument list received for a rest argument
     * of the function argument declaration is not supported.
     *
     * @param array|Number $value
     *
     * @return array<string, array|Number>
     */
    public function get_argument_list_keywords($value): array
    {
        if ($value[0] !== Type::T_LIST || !isset($value[3]) || !\is_array($value[3])) {
            throw new \InvalidArgumentException('The argument is not a sass argument list.');
        }
        return $value[3];
    }
    /**
     * Assert value is a color
     *
     * @param array|Number $value
     *
     * @throws SassScriptException
     */
    public function assert_color($value, ?string $var_name = null): array
    {
        if ($value[0] === Type::T_COLOR) {
            assert(\is_array($value));
            return $value;
        }
        $value = $this->compile_value($value);
        throw Sass_Script_Exception::for_argument("{$value} is not a color.", $var_name);
    }
    /**
     * Assert value is a number
     *
     * @param array|Number $value
     *
     * @throws SassScriptException
     */
    public function assert_number($value, ?string $var_name = null): Number
    {
        if (!$value instanceof Number) {
            $value = $this->compile_value($value);
            throw Sass_Script_Exception::for_argument("{$value} is not a number.", $var_name);
        }
        return $value;
    }
    /**
     * Assert value is a integer
     *
     * @param array|Number $value
     *
     * @throws SassScriptException
     */
    public function assert_integer($value, ?string $var_name = null): int
    {
        $value = $this->assert_number($value, $var_name)->get_dimension();
        if (round($value - \intval($value), Number::PRECISION) > 0) {
            throw Sass_Script_Exception::for_argument("{$value} is not an integer.", $var_name);
        }
        return intval($value);
    }
    /**
     * Compiles a primitive value into a string for debugging purposes.
     *
     * Values in scssphp are typed by being wrapped in arrays, their format is
     * typically:
     *
     *     array(type, contents [, additional_contents]*)
     *
     * @param array|Number $value
     */
    public function compile_value($value): string
    {
        return (string) $this->legacy_value_to_value($value);
    }
}