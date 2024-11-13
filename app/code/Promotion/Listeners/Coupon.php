<?php

namespace Redseanet\Promotion\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Promotion\Model\Coupon as Model;

class Coupon implements ListenerInterface
{
    public function log($event)
    {
        $order = $event['model'];
        if (!empty($order['coupon'])) {
            $coupon = new Model();
            $coupon->load($order->offsetGet('coupon'), 'code');
            $coupon->apply($order->getId(), $order->offsetGet('customer_id') ?: null);
        }
    }
}
