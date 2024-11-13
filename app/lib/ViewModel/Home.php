<?php

namespace Redseanet\Lib\ViewModel;

use CssMin;
use Error;
use Exception;
use JShrink\Minifier;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Stdlib\Singleton;

/**
 * Head view model
 */
final class Home extends Template implements Singleton
{
    protected static $instance = null;
    protected $base = null;

    private function __construct()
    {
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }
}
