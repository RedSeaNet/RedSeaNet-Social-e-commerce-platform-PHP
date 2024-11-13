<?php

namespace Redseanet\Checkout\ViewModel\Order;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Cart;
use Redseanet\Sales\Source\PaymentMethod;

class Payment extends Template
{
    public function getPaymentMethods($address, $items)
    {
        return (new PaymentMethod())->getSourceArray($address, $items, true);
    }

    public function getCurrentMethod()
    {
        return Cart::instance()->offsetGet('payment_method');
    }
}
