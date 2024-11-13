<?php

namespace Redseanet\Promotion\Model\Collection\Coupon;

use Redseanet\Lib\Model\AbstractCollection;

class Log extends AbstractCollection
{
    protected function construct()
    {
        $this->init('promotion_coupon_log');
    }
}
