<?php

namespace Redseanet\Promotion\Model\Coupon;

use Redseanet\Lib\Model\AbstractModel;

class Log extends AbstractModel
{
    protected function construct()
    {
        $this->init('promotion_coupon_log', 'id', ['id', 'coupon_id', 'order_id', 'customer_id']);
    }
}
