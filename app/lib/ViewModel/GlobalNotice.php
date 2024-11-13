<?php

namespace Redseanet\Lib\ViewModel;

use Redseanet\Lib\Stdlib\Singleton;

/**
 * Global notice view model
 */
final class GlobalNotice extends Template implements Singleton
{
    protected static $instance = null;

    private function __construct()
    {
        $this->setTemplate('page/globalNotice');
        $this->cacheKey = 'GLOBAL_NOTICE';
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get the notice customized at the admin page
     *
     * @return string
     */
    public function getNotice()
    {
        return $this->getContainer()->get('config')['theme/global/notice'];
    }
}
