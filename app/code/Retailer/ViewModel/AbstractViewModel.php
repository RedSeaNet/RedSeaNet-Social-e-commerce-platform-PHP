<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Retailer\Model\Retailer;

abstract class AbstractViewModel extends Template
{
    private static $retailer = null;
    private static $store = null;

    public function getRetailer()
    {
        if (is_null(self::$retailer)) {
            self::$retailer = new Retailer();
            $segment = new Segment('customer');
            self::$retailer->load($segment->get('customer')['id'], 'customer_id');
        }
        return self::$retailer;
    }

    public function getStore()
    {
        if (is_null(self::$store)) {
            self::$store = $this->getRetailer()->getStore();
        }
        return self::$store;
    }
}
