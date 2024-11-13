<?php

namespace Redseanet\Checkout\ViewModel\Order;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Source\ShippingMethod;
use Redseanet\Sales\Model\Cart;

class Shipping extends Template
{
    public function getShippingMethods($isVirtual, $address, $items)
    {
        if ($isVirtual) {
            return [];
        }
        return (new ShippingMethod())->getSourceArray($address, $items);
    }

    public function getCurrentMethod()
    {
        if ($method = !empty(Cart::instance()->offsetGet('shipping_method')) ? json_decode(Cart::instance()->offsetGet('shipping_method'), true) : []) {
            return $method;
        }
        return [];
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }
}
