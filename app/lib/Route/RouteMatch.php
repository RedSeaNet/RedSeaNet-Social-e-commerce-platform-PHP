<?php

namespace Redseanet\Lib\Route;

use ArrayAccess;
use Redseanet\Lib\Http\Request;

/**
 * Signed a success route path
 */
class RouteMatch implements ArrayAccess
{
    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var array
     */
    protected $options = null;

    /**
     * @param array $options
     * @param Request $request
     */
    public function __construct($options = [], $request = null)
    {
        $this->options = $options;
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request ?: new Request();
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return isset($this->options['action']) && $this->options['action'] ? $this->options['action'] . 'Action' : 'indexAction';
    }

    public function offsetExists($offset): bool
    {
        if (isset($this->options[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->options[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->options[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->options[$offset]);
    }
}
