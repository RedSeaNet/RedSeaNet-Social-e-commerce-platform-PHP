<?php

namespace Redseanet\Lib\Stdlib;

/**
 * Singleton mode.
 * It should provide a private/protected construct method
 */
interface Singleton
{
    /**
     * @return Singleton
     */
    public static function instance();
}
