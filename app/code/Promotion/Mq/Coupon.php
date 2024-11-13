<?php

namespace Redseanet\Promotion\Mq;

use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Promotion\Model\Coupon as Model;
use Redseanet\Sales\Model\Order;

class Coupon implements MqInterface
{
    public function log($data)
    {
        for ($i = 0; $i < count($data['ids']); $i++) {
            $order = new Order();
            $order->load($data['ids'][$i]);
            if (!empty($order['coupon'])) {
                $coupon = new Model();
                $coupon->load($order->offsetGet('coupon'), 'code');
                $coupon->apply($order->getId(), $order->offsetGet('customer_id') ?: null);
            }
        }
    }
}
