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
namespace Scss_Php\Scss_Php\Extend;

/**
 * @template T of object
 * @template-implements \IteratorAggregate<int, T>
 *
 * @internal
 */
final class Object_Set implements \IteratorAggregate
{
    /**
     * @var \SplObjectStorage<T, mixed>
     */
    private readonly \Spl_Object_Storage $storage;
    public function __construct()
    {
        $this->storage = new \Spl_Object_Storage();
    }
    /**
     * @param T $value
     */
    public function contains(object $value): bool
    {
        return $this->storage->offsetExists($value);
    }
    /**
     * @param T $value
     */
    public function add(object $value): void
    {
        $this->storage->offsetSet($value);
    }
    /**
     * @param ObjectSet<T> $set
     */
    public function add_all(self $set): void
    {
        $this->storage->add_all($set->storage);
    }
    public function getIterator(): \Traversable
    {
        return $this->storage;
    }
}