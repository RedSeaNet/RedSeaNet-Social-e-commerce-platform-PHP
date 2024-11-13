<?php

namespace Redseanet\Lib\Translator;

use ArrayAccess;
use Closure;
use JsonSerializable;

/**
 * Save splid translatation pairs
 */
class Category implements ArrayAccess, JsonSerializable
{
    protected $storage;

    public function __construct($input = [])
    {
        $this->storage = $input;
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function offsetExists($offset): bool
    {
        if (isset($this->storage[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    public function &offsetGet(mixed $offset): mixed
    {
        return $this->storage[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($value)) {
            if ($value instanceof Closure) {
                $value = $value($this);
            } else {
                $value = serialize($value);
            }
        }
        $this->storage[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
    }

    public function serialize()
    {
        return serialize($this->storage);
    }

    public function unserialize($serialized)
    {
        $this->storage = unserialize($serialized);
    }

    public function jsonSerialize(): mixed
    {
        return json_encode($this->storage);
    }

    public function getArrayCopy()
    {
        return $this->storage;
    }

    public function merge(...$arrays)
    {
        foreach ($arrays as $array) {
            if (is_object($array) && is_callable([$array, 'getArrayCopy'])) {
                $array = $array->getArrayCopy();
            }
            $this->storage += $array;
        }
        return $this;
    }
}
