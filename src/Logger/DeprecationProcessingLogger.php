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
namespace Scss_Php\Scss_Php\Logger;

use Scss_Php\Scss_Php\Deprecation;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Runtime_Exception;
use Scss_Php\Scss_Php\Stack_Trace\Trace;
use Source_Span\File_Span;
use Source_Span\Source_Span;
/**
 * A logger that wraps an inner logger to have special handling for
 * deprecation warnings, silencing, making fatal, enabling future, and/or
 * limiting repetition based on its inputs.
 *
 * @internal
 */
final class Deprecation_Processing_Logger implements Logger_Interface
{
    private const MAX_REPETITIONS = 5;
    /**
     * A map of how many times each deprecation has been emitted by this logger.
     *
     * @var array<value-of<Deprecation>, int>
     */
    private array $warning_counts = [];
    /**
     * @param Deprecation[] $silenceDeprecations
     * @param Deprecation[] $fatalDeprecations
     * @param Deprecation[] $futureDeprecations
     */
    public function __construct(
        private readonly Logger_Interface $inner,
        /**
         * Deprecation warnings of these types will be ignored.
         */
        private readonly array $silence_deprecations,
        /**
         * Deprecation warnings of one of these types will cause an error to be
         * thrown.
         *
         * Future deprecations in this list will still cause an error even if they
         * are not also in {@see $futureDeprecations}.
         */
        private readonly array $fatal_deprecations,
        /**
         * Future deprecations that the user has explicitly opted into.
         */
        private readonly array $future_deprecations,
        private readonly bool $limit_repetition = true
    )
    {
    }
    /**
     * Warns if any of the deprecations options are incompatible or unnecessary.
     */
    public function validate(): void
    {
        foreach ($this->fatal_deprecations as $deprecation) {
            if ($deprecation->is_future() && !\in_array($deprecation, $this->future_deprecations, true)) {
                $this->warn("Future {$deprecation->value} deprecation must be enabled before it can be made fatal.");
            } elseif ($deprecation->get_obsolete_in() !== null) {
                $this->warn("{$deprecation->value} deprecation is obsolete, so does not need to be made fatal.");
            } elseif (\in_array($deprecation, $this->silence_deprecations, true)) {
                $this->warn("Ignoring setting to silence {$deprecation->value} deprecation, since it has also been made fatal.");
            }
        }
        foreach ($this->silence_deprecations as $deprecation) {
            if ($deprecation === Deprecation::userAuthored) {
                $this->warn('User-authored deprecations should not be silenced.');
            } elseif ($deprecation->get_obsolete_in() !== null) {
                $this->warn("{$deprecation->value} deprecation is obsolete. If you were previously silencing it, your code may now behave in unexpected ways.");
            } elseif ($deprecation->is_future() && \in_array($deprecation, $this->future_deprecations, true)) {
                $this->warn("Conflicting options for future {$deprecation->value} deprecation cancel each other out.");
            } elseif ($deprecation->is_future()) {
                $this->warn("Future {$deprecation->value} deprecation is not yet active, so silencing it is unnecessary.");
            }
        }
        foreach ($this->future_deprecations as $deprecation) {
            if (!$deprecation->is_future()) {
                $this->warn("{$deprecation->value} is not a future deprecation, so it does not need to be explicitly enabled.");
            }
        }
    }
    public function warn(string $message, ?Deprecation $deprecation = null, ?File_Span $span = null, ?Trace $trace = null): void
    {
        if ($deprecation !== null) {
            $this->handle_deprecation($deprecation, $message, $span, $trace);
        } else {
            $this->inner->warn($message, $deprecation, $span, $trace);
        }
    }
    /**
     * Processes a deprecation warning.
     *
     * If $deprecation is in {@see $fatalDeprecations}, this shows an error.
     *
     * If it's a future deprecation that hasn't been opted into or it's a
     * deprecation that's already been warned for {@see self::MAX_REPETITIONS} times and
     * {@see limitRepetitions} is true, the warning is dropped.
     *
     * Otherwise, this is passed on to {@see warn}.
     */
    private function handle_deprecation(Deprecation $deprecation, string $message, ?File_Span $span = null, ?Trace $trace = null): void
    {
        if ($deprecation->is_future() && !\in_array($deprecation, $this->future_deprecations, true)) {
            return;
        }
        if (\in_array($deprecation, $this->fatal_deprecations, true)) {
            $message .= "\n\nThis is only an error because you've set the {$deprecation->value} deprecation to be fatal.\nRemove this setting if you need to keep using this feature.";
            if ($span !== null && $trace !== null) {
                throw new Simple_Sass_Runtime_Exception($message, $span, $trace);
            }
            if ($span !== null) {
                throw new Simple_Sass_Exception($message, $span);
            }
            throw new Sass_Script_Exception($message);
        }
        if (\in_array($deprecation, $this->silence_deprecations, true)) {
            return;
        }
        if ($this->limit_repetition) {
            $count = $this->warning_counts[$deprecation->value] = ($this->warning_counts[$deprecation->value] ?? 0) + 1;
            if ($count > self::MAX_REPETITIONS) {
                return;
            }
        }
        $this->inner->warn($message, $deprecation, $span, $trace);
    }
    public function debug(string $message, Source_Span $span): void
    {
        $this->inner->debug($message, $span);
    }
    /**
     * Prints a warning indicating the number of deprecation warnings that were
     * omitted due to repetition.
     */
    public function summarize(): void
    {
        $total = 0;
        foreach ($this->warning_counts as $count) {
            if ($count > self::MAX_REPETITIONS) {
                $total += $count - self::MAX_REPETITIONS;
            }
        }
        if ($total > 0) {
            $this->inner->warn("{$total} repetitive deprecation warnings omitted.\nRun in verbose mode to see all warnings.");
        }
    }
}