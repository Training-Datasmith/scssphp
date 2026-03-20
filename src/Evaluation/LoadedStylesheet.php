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

use Scss_Php\Scss_Php\Ast\Sass\Statement\Stylesheet;
use Scss_Php\Scss_Php\Importer\Importer;
/**
 * The result of loading a stylesheet via {@see EvaluateVisitor::loadStylesheet}.
 *
 * @internal
 */
final class Loaded_Stylesheet
{
    public function __construct(
        /**
         * The stylesheet itself.
         */
        private readonly Stylesheet $stylesheet,
        private readonly Importer $importer,
        /**
         * Whether this load counts as a dependency.
         *
         * That is, whether this was (transitively) loaded through a load path or
         * importer rather than relative to the entrypoint.
         */
        private readonly bool $dependency
    )
    {
    }
    public function get_stylesheet(): Stylesheet
    {
        return $this->stylesheet;
    }
    public function get_importer(): Importer
    {
        return $this->importer;
    }
    public function is_dependency(): bool
    {
        return $this->dependency;
    }
}