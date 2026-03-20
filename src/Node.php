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

/**
 * Base node
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
abstract class Node
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $source_index;
    /**
     * @var int|null
     */
    public $source_line;
    /**
     * @var int|null
     */
    public $source_column;
}