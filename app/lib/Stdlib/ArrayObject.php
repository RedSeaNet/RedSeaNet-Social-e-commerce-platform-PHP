<?php

namespace Redseanet\Lib\Stdlib;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Simplify PHP ArrayObject
 */
class ArrayObject implements ArrayAccess, Countable, JsonSerializable, IteratorAggregate
{
    /**
     * @var array
     */
    protected $storage = [];

    /**
     * Returns the value at the specified key by reference
     *
     * @param  mixed $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Returns whether the requested key exists
     *
     * @param  mixed $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param  mixed $key
     * @param  mixed $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Unsets the value at the specified key
     *
     * @param  mixed $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Returns whether the requested key exists
     *
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        if (isset($this->storage[$key])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the value at the specified key
     *
     * @param  mixed $key
     * @return mixed
     */
    public function &offsetGet(mixed $key): mixed
    {
        $ret = null;
        if (!$this->offsetExists($key)) {
            return $ret;
        }
        $ret = &$this->storage[$key];
        return $ret;
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param  scalar $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->storage[$key] = $value instanceof Closure ? $value() : $value;
    }

    /**
     * Unsets the value at the specified key
     *
     * @param  mixed $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        if ($this->offsetExists($key)) {
            unset($this->storage[$key]);
        }
    }

    /**
     * Serialize an ArrayObject to json
     *
     * @return string
     */
    public function jsonSerialize(): mixed
    {
        $result = [];
        foreach ($this->storage as $key => $value) {
            if (is_object($value)) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }
        return json_encode($result);
    }

    /**
     * Serialize an ArrayObject
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(
            array_filter(get_object_vars($this), function ($value) {
                return !is_object($value);
            })
        );
    }

    /**
     * Unserialize an ArrayObject
     *
     * @param  string $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        if ($this instanceof Singleton) {
            static::$instance = $this;
        }
    }

    /**
     * Get data array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    /**
     * Get data array
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->storage;
    }

    /**
     * Retrieve an external iterator
     *
     * @return ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->storage);
    }

    /**
     * Walk collection
     *
     * @param callable $callback
     */
    public function walk(callable $callback, ...$params)
    {
        array_walk($this->storage, $callback, $params);
    }

    /**
     * Combine data from array
     *
     * @param \Traversable|array $array
     * @return ArrayObject
     */
    public function fromArray($array)
    {
        $this->storage += $array;
        return $this;
    }

    /**
     * Get count of array
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->storage);
    }
}
