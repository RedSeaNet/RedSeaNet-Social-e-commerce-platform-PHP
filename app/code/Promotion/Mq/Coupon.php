<?php

namespace Redseanet\Promotion\Mq;

use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Promotion\Model\Coupon as Model;
use Redseanet\Sales\Model\Order;

class Coupon implements MqInterface {

    public function log($data) {
        if (!empty($data['coupon'])) {
            $coupon = new Model();
            $coupon->load($data['coupon'], 'code');
            $coupon->apply($data['id'], $data['customer_id'] ?: null);
        }
    }

}
