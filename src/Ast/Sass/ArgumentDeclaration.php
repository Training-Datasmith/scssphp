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
namespace Scss_Php\Scss_Php\Ast\Sass;

use League\Uri\Contracts\Uri_Interface;
use Scss_Php\Scss_Php\Exception\Multi_Span_Sass_Script_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Format_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Logger\Logger_Interface;
use Scss_Php\Scss_Php\Parser\Scss_Parser;
use Scss_Php\Scss_Php\Util\Character;
use Scss_Php\Scss_Php\Util\Span_Util;
use Scss_Php\Scss_Php\Util\String_Util;
use Source_Span\File_Span;
/**
 * An argument declaration, as for a function or mixin definition.
 *
 * @internal
 */
final class Argument_Declaration implements Sass_Node
{
    private readonly File_Span $span;
    /**
     * @param list<Argument> $arguments
     */
    public function __construct(private readonly array $arguments, File_Span $span, private readonly ?string $rest_argument = null)
    {
        $this->span = $span;
    }
    public static function create_empty(File_Span $span): Argument_Declaration
    {
        return new self([], $span);
    }
    /**
     * Parses an argument declaration from $contents, which should be of the
     * form `@rule name(args) {`.
     *
     * If passed, $url is the name of the file from which $contents comes.
     *
     * @throws SassFormatException if parsing fails.
     */
    public static function parse(string $contents, ?Logger_Interface $logger = null, ?Uri_Interface $url = null): Argument_Declaration
    {
        return (new Scss_Parser($contents, $logger, $url))->parse_argument_declaration();
    }
    public function is_empty(): bool
    {
        return \count($this->arguments) === 0 && $this->rest_argument === null;
    }
    /**
     * @return list<Argument>
     */
    public function get_arguments(): array
    {
        return $this->arguments;
    }
    public function get_rest_argument(): ?string
    {
        return $this->rest_argument;
    }
    public function get_span(): File_Span
    {
        return $this->span;
    }
    /**
     * Returns {@see $span} expanded to include an identifier immediately before the
     * declaration, if possible.
     */
    public function get_span_with_name(): File_Span
    {
        $text = $this->span->get_file()->get_text(0);
        // Move backwards through any whitespace between the name and the arguments.
        $i = $this->span->get_start()->get_offset() - 1;
        while ($i > 0 && Character::is_whitespace($text[$i])) {
            $i--;
        }
        // Then move backwards through the name itself.
        if (!Character::is_name($text[$i])) {
            return $this->span;
        }
        $i--;
        while ($i >= 0 && Character::is_name($text[$i])) {
            $i--;
        }
        // Trim because it's possible that this span is empty (for example, a mixin
        // may be declared without an argument list).
        return Span_Util::trim($this->span->get_file()->span($i + 1, $this->span->get_end()->get_offset()));
    }
    /**
     * @param array<string, mixed> $names Only keys are relevant
     *
     * @throws SassScriptException if $positional and $names aren't valid for this argument declaration.
     */
    public function verify(int $positional, array $names): void
    {
        $name_used = 0;
        foreach ($this->arguments as $i => $argument) {
            if ($i < $positional) {
                if (isset($names[$argument->get_name()])) {
                    $original_name = $this->original_argument_name($argument->get_name());
                    throw new Sass_Script_Exception(sprintf('Argument %s was passed both by position and by name.', $original_name));
                }
            } elseif (isset($names[$argument->get_name()])) {
                $name_used++;
            } elseif ($argument->get_default_value() === null) {
                $original_name = $this->original_argument_name($argument->get_name());
                throw new Multi_Span_Sass_Script_Exception(sprintf('Missing argument %s.', $original_name), 'invocation', ['declaration' => $this->get_span_with_name()]);
            }
        }
        if ($this->rest_argument !== null) {
            return;
        }
        if ($positional > \count($this->arguments)) {
            $message = sprintf('Only %d %s%s allowed, but %d %s passed.', \count($this->arguments), empty($names) ? '' : 'positional ', String_Util::pluralize('argument', \count($this->arguments)), $positional, String_Util::pluralize('was', $positional, 'were'));
            throw new Multi_Span_Sass_Script_Exception($message, 'invocation', ['declaration' => $this->get_span_with_name()]);
        }
        if ($name_used < \count($names)) {
            $unknown_names = array_values(array_diff(array_keys($names), array_map(fn(\Scss_Php\Scss_Php\Ast\Sass\Argument $argument): string => $argument->get_name(), $this->arguments)));
            \assert(\count($unknown_names) > 0);
            $message = sprintf('No %s named %s.', String_Util::pluralize('argument', \count($unknown_names)), String_Util::to_sentence(array_map(fn($name): string => '$' . $name, $unknown_names), 'or'));
            throw new Multi_Span_Sass_Script_Exception($message, 'invocation', ['declaration' => $this->get_span_with_name()]);
        }
    }
    private function original_argument_name(string $name): string
    {
        if ($name === $this->rest_argument) {
            $text = $this->span->get_text();
            $last_dollar = strrpos($text, '$');
            assert($last_dollar !== false);
            $from_dollar = substr($text, $last_dollar);
            $dot = strrpos($from_dollar, '.');
            assert($dot !== false);
            return substr($from_dollar, 0, $dot);
        }
        foreach ($this->arguments as $argument) {
            if ($argument->get_name() === $name) {
                return $argument->get_original_name();
            }
        }
        throw new \InvalidArgumentException("This declaration has no argument named \"\${$name}\".");
    }
    /**
     * Returns whether $positional and $names are valid for this argument
     * declaration.
     *
     * @param array<string, mixed> $names Only keys are relevant
     */
    public function matches(int $positional, array $names): bool
    {
        $name_used = 0;
        foreach ($this->arguments as $i => $argument) {
            if ($i < $positional) {
                if (isset($names[$argument->get_name()])) {
                    return false;
                }
            } elseif (isset($names[$argument->get_name()])) {
                $name_used++;
            } elseif ($argument->get_default_value() === null) {
                return false;
            }
        }
        if ($this->rest_argument !== null) {
            return true;
        }
        if ($positional > \count($this->arguments)) {
            return false;
        }
        if ($name_used < \count($names)) {
            return false;
        }
        return true;
    }
    public function __toString(): string
    {
        $parts = [];
        foreach ($this->arguments as $arg) {
            $parts[] = "\${$arg}";
        }
        if ($this->rest_argument !== null) {
            $parts[] = "\${$this->rest_argument}...";
        }
        return implode(', ', $parts);
    }
}