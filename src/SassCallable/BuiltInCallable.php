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
namespace Scss_Php\Scss_Php\Sass_Callable;

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Ast\Sass\Argument_Declaration;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Value\Sass_Null;
use Scss_Php\Scss_Php\Value\Value;
/**
 * A callable defined in PHP code.
 *
 * Unlike user-defined callables, built-in callables support overloads. They
 * may declare multiple different callbacks with multiple different sets of
 * arguments. When the callable is invoked, the first callback with matching
 * arguments is invoked.
 *
 * @internal
 */
class Built_In_Callable implements Sass_Callable
{
    /**
     * Creates a function with a single $arguments declaration and a single
     * $callback.
     *
     * The argument declaration is parsed from $arguments, which should not
     * include parentheses. Throws a {@see SassFormatException} if parsing fails.
     *
     * If passed, $url is the URL of the module in which the function is
     * defined.
     *
     * @param callable(list<Value>): Value $callback
     *
     * @throws SassFormatException
     */
    public static function function(string $name, string $arguments, callable $callback, ?Uri_Interface $url = null): Built_In_Callable
    {
        return self::parsed($name, Argument_Declaration::parse("@function {$name}({$arguments}) {", url: $url), $callback);
    }
    /**
     * Creates a mixin with a single $arguments declaration and a single
     * $callback.
     *
     * The argument declaration is parsed from $arguments, which should not
     * include parentheses. Throws a {@see SassFormatException} if parsing fails.
     *
     * If passed, $url is the URL of the module in which the mixin is
     * defined.
     *
     * @param callable(list<Value>): void $callback
     *
     * @throws SassFormatException
     */
    public static function mixin(string $name, string $arguments, callable $callback, ?Uri_Interface $url = null, bool $accepts_content = false): Built_In_Callable
    {
        return self::parsed($name, Argument_Declaration::parse("@mixin {$name}({$arguments}) {", url: $url), function ($arguments) use ($callback): \Scss_Php\Scss_Php\Value\Sass_Null {
            $callback($arguments);
            return Sass_Null::create();
        }, $accepts_content);
    }
    /**
     * Creates a function with multiple implementations.
     *
     * Each key/value pair in $overloads defines the argument declaration for
     * the overload (which should not include parentheses), and the callback to
     * execute if that argument declaration matches. Throws a
     * {@see SassFormatException} if parsing fails.
     *
     * If passed, $url is the URL of the module in which the function is
     * defined.
     *
     * @param array<string, callable(list<Value>): Value> $overloads
     *
     * @throws SassFormatException
     */
    public static function overloaded_function(string $name, array $overloads, ?Uri_Interface $url = null): Built_In_Callable
    {
        $processed_overloads = [];
        foreach ($overloads as $args => $callback) {
            $processed_overloads[] = [Argument_Declaration::parse("@function {$name}({$args}) {", url: $url), $callback];
        }
        return new Built_In_Callable($name, $processed_overloads, false);
    }
    /**
     * Creates a callable with a single $arguments declaration and a single $callback.
     *
     * @param callable(list<Value>): Value $callback
     */
    private static function parsed(string $name, Argument_Declaration $arguments, callable $callback, bool $accepts_content = false): Built_In_Callable
    {
        return new Built_In_Callable($name, [[$arguments, $callback]], $accepts_content);
    }
    /**
     * @param list<array{ArgumentDeclaration, callable(list<Value>): Value}> $overloads
     */
    private function __construct(private readonly string $name, private readonly array $overloads, private readonly bool $accepts_content)
    {
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function accepts_content(): bool
    {
        return $this->accepts_content;
    }
    /**
     * Returns the argument declaration and PHP callback for the given
     * positional and named arguments.
     *
     * If no exact match is found, finds the closest approximation. Note that this
     * doesn't guarantee that $positional and $names are valid for the returned
     * {@see ArgumentDeclaration}.
     *
     * @param array<string, mixed> $names Only the keys are relevant
     *
     * @return array{ArgumentDeclaration, callable(list<Value>): Value}
     */
    public function callback_for(int $positional, array $names): array
    {
        $fuzzy_match = null;
        $min_mismatch_distance = null;
        foreach ($this->overloads as $overload) {
            // Ideally, find an exact match.
            if ($overload[0]->matches($positional, $names)) {
                return $overload;
            }
            $mismatch_distance = \count($overload[0]->get_arguments()) - $positional;
            if ($min_mismatch_distance !== null) {
                if (abs($mismatch_distance) > abs($min_mismatch_distance)) {
                    continue;
                }
                // If two overloads have the same mismatch distance, favor the overload
                // that has more arguments.
                if (abs($mismatch_distance) === abs($min_mismatch_distance) && $mismatch_distance < 0) {
                    continue;
                }
            }
            $min_mismatch_distance = $mismatch_distance;
            $fuzzy_match = $overload;
        }
        if ($fuzzy_match !== null) {
            return $fuzzy_match;
        }
        throw new \LogicException("BuiltInCallable {$this->name} may not have empty overloads.");
    }
    /**
     * Returns a copy of this callable with the given $name.
     */
    public function with_name(string $name): Built_In_Callable
    {
        return new Built_In_Callable($name, $this->overloads, $this->accepts_content);
    }
}