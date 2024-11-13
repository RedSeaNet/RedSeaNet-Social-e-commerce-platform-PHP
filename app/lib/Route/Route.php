<?php

namespace Redseanet\Lib\Route;

/**
 * Customize router
 */
abstract class Route implements RouteInterface
{
    public function serialize()
    {
        return serialize(
            array_filter(get_object_vars($this), function ($value) {
                return !is_object($value);
            })
        );
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function __set_state(array $array)
    {
        return new static();
    }
}
