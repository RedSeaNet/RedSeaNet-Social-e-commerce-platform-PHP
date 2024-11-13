<?php

namespace Redseanet\Sales\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart as CartSingleton;
use Redseanet\Sales\Model\Collection\Cart as Collection;

class Cart implements ListenerInterface
{
    public function afterCurrencySwitch($event)
    {
        $code = $event['code'];
        CartSingleton::instance()->convertPrice($code);
    }

    public function afterLoggedIn($event)
    {
        $customer = $event['model'];
        $collection = new Collection();
        $collection->where([
            'customer_id' => $customer->getId(),
            'status' => 1
        ])->order('id DESC');
        if ($collection->count()) {
            $cart = new CartSingleton($collection->toArray()[0]);
            $cart->combine(CartSingleton::instance());
        } elseif (CartSingleton::instance()->getId()) {
            CartSingleton::instance()
                    ->setData('customer_id', $customer->getId())
                    ->save();
        } else {
            CartSingleton::instance()->regenerate();
        }
    }

    public function afterLoggedOut()
    {
        $segment = new Segment('customer');
        if ($segment->get('cart')) {
            $segment->offsetUnset('cart');
        }
    }
}
