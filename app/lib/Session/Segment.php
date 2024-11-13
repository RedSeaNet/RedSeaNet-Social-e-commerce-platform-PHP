<?php

namespace Redseanet\Lib\Session;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Redseanet\Lib\Session;

/**
 * Get/Set values in different domains
 */
class Segment implements IteratorAggregate, ArrayAccess
{
    /**
     * Session object
     * @var Session
     */
    protected $session = null;

    /**
     * Segment identify
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $iterator = [];

    public function __construct($name, Session $session = null)
    {
        $this->name = $name;
        if (is_null($session)) {
            $session = Session::instance();
        }
        $this->session = $session;
        if (!$this->session->sessionExists()) {
            $this->session->start();
        }
        if (!isset($_SESSION[$this->name])) {
            $_SESSION[$this->name] = [];
        } else {
            $this->iterator = $_SESSION[$this->name];
        }
    }

    public function get($key, $default = '')
    {
        return isset($this->iterator[$key]) ? unserialize($this->iterator[$key]) : $default;
    }

    public function set($key, $value)
    {
        if ($value instanceof \Closure) {
            $value = $value();
        }
        $this->iterator[$key] = serialize($value);
        $_SESSION[$this->name][$key] = $this->iterator[$key];
        return $this;
    }

    public function toArray()
    {
        $iterator = [];
        foreach ($this->iterator as $key => $value) {
            $iterator[$key] = unserialize($value);
        }
        return $iterator;
    }

    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetExists($key): bool
    {
        if (isset($this->iterator[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public function &offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset(mixed $key): void
    {
        unset($this->iterator[$key], $_SESSION[$this->name][$key]);
    }

    public function addMessage(array $message)
    {
        if (!isset($this->iterator['message'])) {
            $messages = [];
        } else {
            $messages = array_merge(unserialize($this->iterator['message']), $message);
        }
        $this->set('message', $messages);
    }

    public function getMessage()
    {
        $result = $this->get('message');
        $this->offsetUnset('message');
        return $result;
    }

    public function clear()
    {
        $this->iterator = [];
        $_SESSION[$this->name] = [];
    }

    public function getName()
    {
        return $this->name;
    }
}
