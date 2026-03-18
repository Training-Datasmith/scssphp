<?php

declare(strict_types=1);

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Exception;

use ScssPhp\ScssPhp\StackTrace\Trace;
use ScssPhp\ScssPhp\Util;
use ScssPhp\ScssPhp\Util\ErrorUtil;
use SourceSpan\FileSpan;

/**
 * @internal
 */
class MultiSpanSassException extends \Exception implements SassException
{
    private readonly FileSpan $span;

    /**
     * @param array<string, FileSpan> $secondarySpans
     */
    public function __construct(private readonly string $originalMessage, FileSpan $span, public readonly string $primaryLabel, public readonly array $secondarySpans, ?\Throwable $previous = null)
    {
        $this->span = $span;

        parent::__construct(ErrorUtil::formatErrorMessageMultiple($this->originalMessage, $span, $this->primaryLabel, $this->secondarySpans, $this->getSassTrace()), 0, $previous);
    }

    /**
     * Gets the original message without the location info in it.
     */
    public function getOriginalMessage(): string
    {
        return $this->originalMessage;
    }

    public function getSpan(): FileSpan
    {
        return $this->span;
    }

    public function getSassTrace(): Trace
    {
        return new Trace([Util::frameForSpan($this->span, 'root stylesheet')]);
    }

    public function withAdditionalSpan(FileSpan $span, string $label, ?\Throwable $previous = null): MultiSpanSassException
    {
        return new self($this->originalMessage, $this->span, $this->primaryLabel, $this->secondarySpans + [$label => $span], $previous);
    }

    public function withTrace(Trace $trace, ?\Throwable $previous = null): MultiSpanSassRuntimeException
    {
        return new MultiSpanSassRuntimeException($this->originalMessage, $this->span, $this->primaryLabel, $this->secondarySpans, $trace, $previous);
    }
}
