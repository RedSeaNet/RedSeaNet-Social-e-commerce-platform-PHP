<?php

namespace Redseanet\Lib\EventDispatcher;

use ArrayAccess;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

/**
 * Add parameters to symfony event object
 */
class Event extends SymfonyEvent implements ArrayAccess
{
    protected $storage = [];

    public function __construct($options = [])
    {
        $this->storage = $options;
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function offsetExists($offset): bool
    {
        if (isset($this->storage[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    public function offsetGet($offset): mixed
    {
        return $this->storage[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->storage[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
    }

    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
